<?php
/* v1.6.3.1.202309091205, from surface */

namespace App\Controllers;
use \CodeIgniter\Controller;
use App\Models\Mcommon;

class Dept extends Controller
{
    public function __construct()
    {
        helper(['form', 'url']);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 部门组织结构
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function init($menu_id='')
    {
        // 从session中取出数据
        $session = \Config\Services::session();
        $dept_authz = $session->get($menu_id.'-dept_authz');
        $tree_expand = $session->get('dept_tree_expand');

        $model = new Mcommon();

        $sql = sprintf('
            select GUID,部门编码,部门名称,部门级别,上级部门
            from def_dept
            where 删除标识="0" and 有效标识="1"
                and left(部门编码,length("%s"))="%s"
            order by 部门级别 desc',
            $dept_authz, $dept_authz);

        $query = $model->select($sql);
        $results = $query->getResult();

        $dept_arr = [];

        foreach ($results as $row)
        {
            $item = [];

            $item['sid'] = $row->部门编码;
            $item['id'] = sprintf('部门^%d^%s^%s^%d级', $row->GUID, $row->部门编码, $row->部门名称, $row->部门级别);
            $item['value'] = sprintf('%s (0)',$row->部门名称);
            $item['dept'] = $row->部门名称;
            $item['higher'] = $row->上级部门;
            $item['child_count'] = 0;
            $item['items'] = [];

            array_push($dept_arr, $item);
        }

        $arr_len = count($dept_arr);

        for ($ii=0; $ii<count($dept_arr); $ii++)
        {
            for ($jj=$ii; $jj<count($dept_arr); $jj++)
            {
                if ($dept_arr[$jj]['sid'] == $dept_arr[$ii]['higher'])
                {
                    $dept_arr[$jj]['child_count'] ++;
                    $dept_arr[$jj]['value'] = sprintf('%s (%d)', $dept_arr[$jj]['dept'], $dept_arr[$jj]['child_count']);

                    array_push($dept_arr[$jj]['items'], $dept_arr[$ii]);
                    break;
                }
            }
        }

        $arr = [];
        $arr[0] = $dept_arr[$arr_len-1];

        $send['func_id'] = $menu_id;
        $send['tree_expand_json'] = json_encode($tree_expand);
        $send['dept_json'] = json_encode($arr);

        echo view('Vdept.php', $send);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 条目信息查询
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function ajax($menu_id='', $type='')
    {
        $arg = $this->request->getJSON(true);

        // 记录展开的节点
        if ($arg['操作'] == '展开')
        {
            $expand_arr = [];
            for ($i=count($arg['id_arr']); $i>0; $i--)
            {
                array_push($expand_arr, $arg['id_arr'][$i-1]);
            }

            // 存入session
            $session_arr = [];
            $session_arr['dept_tree_expand'] = $expand_arr;
            $session = \Config\Services::session();
            $session->set($session_arr);
            return;
        }

        $GUID = $arg['id'];

        $model = new Mcommon();

        if ($arg['操作'] == '查询部门信息')
        {
            $sql = sprintf('
                select 部门编码,部门名称,部门级别,负责人,
                    上级部门,下级部门,
                    记录开始日期,记录结束日期
                from def_dept
                where GUID="%s"', $GUID);
            $query = $model->select($sql);
            $results = $query->getResult();

            $rows_arr = [];
            array_push($rows_arr, array('表项'=>'属性', '值'=>'查询部门信息'));
            array_push($rows_arr, array('表项'=>'生效日期', '值'=>''));
            array_push($rows_arr, array('表项'=>'部门编码', '值'=>$results[0]->部门编码));
            array_push($rows_arr, array('表项'=>'部门名称', '值'=>$results[0]->部门名称));
            array_push($rows_arr, array('表项'=>'负责人', '值'=>$results[0]->负责人));
            array_push($rows_arr, array('表项'=>'部门级别', '值'=>$results[0]->部门级别));
            array_push($rows_arr, array('表项'=>'上级部门', '值'=>$results[0]->上级部门));
            array_push($rows_arr, array('表项'=>'下级部门', '值'=>$results[0]->下级部门));
            array_push($rows_arr, array('表项'=>'记录开始日期', '值'=>$results[0]->记录开始日期));
            array_push($rows_arr, array('表项'=>'记录结束日期', '值'=>$results[0]->记录结束日期));
        }

        else if ($arg['操作'] == '新增下级部门')
        {
            $sql = sprintf('
                select 
                    t1.部门编码,t1.部门名称,t1.部门级别,
                    t1.部门级别+1 as 下级部门级别,
                    concat(t1.部门编码,"-",ifnull(t2.下级部门编码,0)+1) as 下级部门编码
                from
                (
                    select 部门编码,部门名称,部门级别
                    from def_dept
                    where GUID="%s"
                ) as t1
                left join
                (
                    select 上级部门,max(substring_index(部门编码,"-",-1)+0) as 下级部门编码
                    from def_dept
                    where 上级部门 in 
                    (
                        select 部门编码
                        from def_dept
                        where GUID="%s"
                    )
                ) as t2
                on t1.部门编码=t2.上级部门', $GUID, $GUID);
            $query = $model->select($sql);
            $results = $query->getResult();

            $rows_arr = [];
            array_push($rows_arr, array('表项'=>'属性', '值'=>'新增下级部门'));
            array_push($rows_arr, array('表项'=>'生效日期', '值'=>''));
            array_push($rows_arr, array('表项'=>'本级部门编码', '值'=>$results[0]->部门编码));
            array_push($rows_arr, array('表项'=>'本级部门名称', '值'=>$results[0]->部门名称));
            array_push($rows_arr, array('表项'=>'本级部门级别', '值'=>$results[0]->部门级别));
            array_push($rows_arr, array('表项'=>'下级部门编码', '值'=>$results[0]->下级部门编码));
            array_push($rows_arr, array('表项'=>'下级部门名称', '值'=>''));
            array_push($rows_arr, array('表项'=>'下级部门级别', '值'=>$results[0]->下级部门级别));
            array_push($rows_arr, array('表项'=>'下级部门负责人', '值'=>''));
        }

        exit(json_encode($rows_arr));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 修改部门信息
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function upkeep($menu_id='')
    {
        $arg = $this->request->getJSON(true);

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_workid = $session->get('user_workid');

        $model = new Mcommon();

        $guid_str = '';
        foreach ($arg['部门'] as $guid)
        {
            if ($guid_str == '')
            {
                $guid_str = sprintf('"%s"', $guid);
            }
            else
            {
                $guid_str = sprintf('%s,"%s"', $guid_str ,$guid);
            }
        }
            
        $update_str = '';
        foreach ($arg as $key => $value)
        {
            if ($key=='操作' || $key=='部门') continue;
            if ($value['更改标识'] == '0') continue;

            if ($update_str != '')
            {
                $update_str = $update_str . ',';
            }
            $update_str = $update_str . $key;
        }

        //增加新记录
        $fld_str = '部门编码,部门名称,部门级别,' .
            '上级部门,下级部门,负责人,' .
            '记录开始日期,记录结束日期,' .
            '操作记录,操作来源,操作人员,' .
            '开始操作时间,结束操作时间,' .
            '校验标识,删除标识,有效标识';
        $fld_arr = explode(',', $fld_str);

        $col_str = '';
        foreach ($fld_arr as $fld)
        {
            $col = $fld;

            switch ($fld)
            {
                case '记录开始日期':
                    $col = sprintf('"%s" as 记录开始日期', $arg['生效日期']['值']);
                    break;
                case '记录结束日期':
                    $col = '"" as 记录结束日期';
                    break;
                case '操作记录':
                    $col = '"新增[2]" as 操作记录';
                    break;
                case '操作来源':
                    $col = '"页面更新" as 操作来源';
                    break;
                case '操作人员':
                    $col = sprintf('"%s" as 操作人员', $user_workid);
                    break;
                case '开始操作时间':
                    $col = sprintf('"%s" as 操作开始时间', date('Y-m-d H:i:s'));
                    break;
                case '结束操作时间':
                    $col = '"" as 结束操作时间';
                    break;
                case '校验标识':
                    $col = '"0" as 校验标识';
                    break;
                case '删除标识':
                    $col = '"0" as 删除标识';
                    break;
                case '有效标识':
                    $col = '"1" as 有效标识';
                    break;
            }

            foreach ($arg as $key => $value)
            {
                if ($key=='操作' || $key=='部门' || $key=='生效日期') continue;
                if ($value['更改标识'] == '0') continue;

                if ($fld == $key)
                {
                    $col = sprintf('"%s" as %s', $value['值'], $key);
                    break;
                }
            }

            if ($col_str != '') $col_str = $col_str . ',';
            $col_str = $col_str . $col;
        }

        $sql_insert = sprintf('
            insert into def_dept (%s)
            select %s
            from def_dept
            where GUID in (%s)', 
            $fld_str, $col_str, $guid_str);

        // 原记录更新
        $sql_update = sprintf('
            update def_dept
            set 记录结束日期="%s",
                操作记录="更新[2],%s",
                操作来源="页面更新",
                操作人员="%s",
                结束操作时间="%s",
                有效标识="0"
            where GUID in (%s)',
            $arg['生效日期']['值'],
            $update_str,
            $user_workid,
            date('Y-m-d H:i:s'),
            $guid_str);

        // 写日志
        $model->sql_log('页面修改', $menu_id, sprintf('表名=def_dept,GUID="%s"', $guid_str));

        $num = $model->exec($sql_insert);
        $num = $model->exec($sql_update);

        exit(sprintf('部门信息修改成功,修改 %d 条记录',$num));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 新增部门信息
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function insert($menu_id='')
    {
        $arg = $this->request->getJSON(true);

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_workid = $session->get('user_workid');

        $model = new Mcommon();

        $sql = sprintf('
            insert into def_dept 
                (部门编码,部门名称,部门级别,
                上级部门,下级部门,负责人,
                记录开始日期,记录结束日期,
                操作记录,操作来源,操作人员,
                开始操作时间,结束操作时间,
                校验标识,删除标识,有效标识) 
            values ("%s","%s",%d,
                "%s","无","%s",
                "%s","",
                "新增", "页面新增", "%s",
                "%s","",
                "0","0","1")',
            $arg['下级部门编码'], $arg['下级部门名称'], $arg['下级部门级别'], 
            $arg['本级部门编码'], $arg['下级部门负责人'],
            $arg['生效日期'],
            $user_workid,
            date('Y-m-d H:m:s'));
        
        // 新增
        $num = $model->exec($sql);

        exit(sprintf('`新增面试信息`成功,新增 %d 条记录',$num));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 删除信息
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function delete_row($menu_id='', $type='')
    {
        $arg = $this->request->getJSON(true);

        $model = new Mcommon();

        $guid_str = '';
        foreach ($arg['部门'] as $guid)
        {
            if ($guid_str == '')
            {
                $guid_str = sprintf('"%s"', $guid);
            }
            else
            {
                $guid_str = sprintf('%s,"%s"', $guid_str ,$guid);
            }
        }

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_workid = $session->get('user_workid');

        //原记录更新
        $sql_update = sprintf('
            update def_dept
            set 记录结束日期="%s",操作记录="删除",
                操作来源="页面",操作人员="%s",
                结束操作时间="%s",
                删除标识="1",有效标识="0"
            where GUID in (%s)',
            date('Y-m-d'), $user_workid, date('Y-m-d H:i:s'), $guid_str);

        // 写日志
        $model->sql_log('删除', $menu_id, sprintf('sql=%s',str_replace('"','',$sql_update)));
        // 删除
        $num = $model->exec($sql_update);

        exit(sprintf('删除成功,删除 %d 条记录',$num));
    }
}