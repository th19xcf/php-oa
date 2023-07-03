<?php
/* v5.1.4.1.202307011135, from home */

namespace App\Controllers;
use \CodeIgniter\Controller;
use App\Models\Mcommon;

class Employee extends Controller
{
    public function __construct()
    {
        helper(['form', 'url']);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 在职人员
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function init($menu_id='')
    {
        $model = new Mcommon();

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_location = $session->get('user_location');

        $sql = sprintf('
            select GUID,姓名,工号1 as 工号,员工状态,
                属地,部门名称,if(班组="","未分班组",班组) as 班组,
                岗位名称,岗位类型,结算类型,培训完成日期
            from ee_onjob
            where 属地="%s" and 有效标识="1" and 删除标识="0"
            order by 员工状态,
                convert(部门名称 using gbk),
                convert(班组 using gbk),
                convert(姓名 using gbk)',
            $user_location);

        $query = $model->select($sql);
        $results = $query->getResult();

        $up3_arr = []; // 班组
        $up2_arr = []; // 部门名称
        $up1_arr = []; // 员工状态

        // 班组
        foreach ($results as $row)
        {
            $ee_arr = [];
            $ee_arr['id'] = sprintf('人员^%s^%s', $row->GUID, $row->姓名);
            $ee_arr['value'] = sprintf('%s (%s)', $row->姓名, $row->岗位名称);

            $up1_id = sprintf('班组^%s^%s^%s', $row->员工状态, $row->部门名称, $row->班组);
            if (array_key_exists($up1_id, $up1_arr) == false)
            {
                $up1_arr[$up1_id] = [];
                $up1_arr[$up1_id]['num'] = 0;
                $up1_arr[$up1_id]['id'] = $up1_id;
                $up1_arr[$up1_id]['value'] = $row->班组;
                $up1_arr[$up1_id]['items'] = [];
            }
            $up1_arr[$up1_id]['num'] = count($up1_arr[$up1_id]['items']) + 1;
            $up1_arr[$up1_id]['value'] = sprintf('%s (%d人)', $row->班组, $up1_arr[$up1_id]['num']);
            array_push($up1_arr[$up1_id]['items'], $ee_arr);
        }

        // 部门
        foreach ($up1_arr as $up1)
        {
            $arr = explode('^', $up1['id']);
            $up2_id = sprintf('部门^%s^%s', $arr[1], $arr[2]);
            if (array_key_exists($up2_id, $up2_arr) == false)
            {
                $up2_arr[$up2_id]['id'] = $up2_id;
                $up2_arr[$up2_id]['num'] = 0;
                $up2_arr[$up2_id]['value'] = $arr[2];
                $up2_arr[$up2_id]['items'] = [];
            }

            $up2_arr[$up2_id]['num'] += $up1['num'];
            $up2_arr[$up2_id]['value'] = sprintf('%s (%d人)', $arr[2], $up2_arr[$up2_id]['num']);
            array_push($up2_arr[$up2_id]['items'], $up1);
        }

        // 员工状态
        foreach ($up2_arr as $up2)
        {
            $arr = explode('^', $up2['id']);
            $up3_id = sprintf('员工状态^%s', $arr[1]);
            if (array_key_exists($up3_id, $up3_arr) == false)
            {
                $up3_arr[$up3_id]['id'] = $up3_id;
                $up3_arr[$up3_id]['num'] = 0;
                $up3_arr[$up3_id]['value'] = $arr[1];
                $up3_arr[$up3_id]['items'] = [];
            }

            $up3_arr[$up3_id]['num'] += $up2['num'];
            $up3_arr[$up3_id]['value'] = sprintf('%s (%d人)', $arr[1], $up3_arr[$up3_id]['num']);
            array_push($up3_arr[$up3_id]['items'], $up2);
        }

        $csr_arr = [];
        $csr_arr['id'] = '0级^入职人员';
        $csr_arr['value'] = '入职人员';
        $csr_arr['items'] = [];
        $csr_num = 0;

        foreach ($up3_arr as $up3)
        {
            $csr_num += $up3['num'];
            $csr_arr['value'] = sprintf('入职人员 (%d人)', $csr_num);
            array_push($csr_arr['items'], $up3);
        }

        $tree_arr = [];
        array_push($tree_arr, $csr_arr);

        //grid
        $grid_arr = [];

        // 直接给一些固定变量赋值
        $object_arr = []; 

        $send['func_id'] = $menu_id;
        $send['tree_json'] = json_encode($tree_arr);
        $send['grid_json'] = json_encode($grid_arr);
        $send['import_func_id'] = '2042';
        $send['import_func_name'] = '培训人员';
        $send['object_json'] = json_encode($object_arr);

        echo view('Vemployee.php', $send);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 条目信息查询
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function ajax($menu_id='', $type='')
    {
        $arg = $this->request->getJSON(true);

        $arr = explode('^', $arg['id']);

        // 读出数据
        $model = new Mcommon();
        $rows_arr = [];

        if ($arr[0] == '部门')
        {
            $dept = $arr[2];

            $sql = sprintf('
                select 部门名称,部门全称
                from view_dept
                where 部门名称="%s"', $dept);
            $query = $model->select($sql);
            $results = $query->getResult();

            if (empty($results))
            {
                array_push($rows_arr, array('表项'=>'属性', '值'=>'部门'));
                array_push($rows_arr, array('表项'=>'部门层级', '值'=>''));
                array_push($rows_arr, array('表项'=>'部门名称', '值'=>'部门表无此部门, 请补充'));
            }
            else
            {
                array_push($rows_arr, array('表项'=>'属性', '值'=>'部门'));
                array_push($rows_arr, array('表项'=>'部门层级', '值'=>$results[0]->部门全称));
                array_push($rows_arr, array('表项'=>'部门名称', '值'=>$results[0]->部门名称));
            }
        }
        else if ($arr[0] == '班组')
        {
            $team = $arr[3];

            $sql = sprintf('
                select 部门名称,部门全称
                from view_dept
                where 部门名称="%s"', $team);
            $query = $model->select($sql);
            $results = $query->getResult();

            if (empty($results))
            {
                array_push($rows_arr, array('表项'=>'属性', '值'=>'部门'));
                array_push($rows_arr, array('表项'=>'部门层级', '值'=>''));
                array_push($rows_arr, array('表项'=>'班组', '值'=>'部门表无此班组, 请补充'));
            }
            else
            {
                array_push($rows_arr, array('表项'=>'属性', '值'=>'班组'));
                array_push($rows_arr, array('表项'=>'部门层级', '值'=>$results[0]->部门全称));
                array_push($rows_arr, array('表项'=>'班组', '值'=>$results[0]->部门名称));
            }
        }
        else if ($arr[0] == '人员')
        {
            $sql = sprintf('
                select 姓名,身份证号,员工状态,
                    培训开始日期,培训完成日期,
                    一阶段日期,二阶段日期,
                    岗位名称,岗位类型,结算类型,
                    部门名称,班组,工号1,
                    离职日期,离职原因
                from ee_onjob
                where GUID="%s"', $arr[1]);
            $query = $model->select($sql);
            $results = $query->getResult();

            array_push($rows_arr, array('表项'=>'属性', '值'=>'查询人员信息'));
            array_push($rows_arr, array('表项'=>'生效日期', '值'=>''));
            array_push($rows_arr, array('表项'=>'姓名', '值'=>$results[0]->姓名));
            array_push($rows_arr, array('表项'=>'岗位名称', '值'=>$results[0]->岗位名称));
            array_push($rows_arr, array('表项'=>'岗位类型', '值'=>$results[0]->岗位类型));
            array_push($rows_arr, array('表项'=>'结算类型', '值'=>$results[0]->结算类型));
            array_push($rows_arr, array('表项'=>'部门名称', '值'=>$results[0]->部门名称));
            array_push($rows_arr, array('表项'=>'班组', '值'=>$results[0]->班组));
            array_push($rows_arr, array('表项'=>'工号1', '值'=>$results[0]->工号1));
            array_push($rows_arr, array('表项'=>'员工状态', '值'=>$results[0]->员工状态));
            array_push($rows_arr, array('表项'=>'培训开始日期', '值'=>$results[0]->培训开始日期));
            array_push($rows_arr, array('表项'=>'培训完成日期', '值'=>$results[0]->培训完成日期));
            array_push($rows_arr, array('表项'=>'一阶段日期', '值'=>$results[0]->一阶段日期));
            array_push($rows_arr, array('表项'=>'二阶段日期', '值'=>$results[0]->二阶段日期));
            array_push($rows_arr, array('表项'=>'离职日期', '值'=>$results[0]->离职日期));
            array_push($rows_arr, array('表项'=>'离职原因', '值'=>$results[0]->离职原因));
        }
        else
        {
            array_push($rows_arr, array('表项'=>'属性', '值'=>''));
        }

        exit(json_encode($rows_arr));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 人员信息修改
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function upkeep($menu_id='', $type='')
    {
        $arg = $this->request->getJSON(true);

        $model = new Mcommon();

        $guid_str = '';
        foreach ($arg['人员'] as $guid)
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

        // 处理离职信息
        if (array_key_exists('员工状态',$arg))
        {
            if ($arg['员工状态']['值'] == '离职')
            {
                //更新该员工的记录
                $sql = sprintf('
                    update ee_onjob
                    set 员工状态="%s",
                        离职日期="%s",
                        离职原因="%s",
                        记录结束日期=if(记录结束日期="","%s",记录结束日期)
                    where concat(身份证号,入职次数) in
                        (
                            select concat(身份证号,入职次数)
                            from
                            (
                                select 身份证号,入职次数
                                from ee_onjob
                                where GUID in (%s)
                            ) as ta
                        )
                        and 员工状态!="离职"',
                    $arg['员工状态']['值'], $arg['离职日期']['值'], 
                    $arg['离职原因']['值'], $arg['离职日期']['值'], 
                    $guid_str);

                $num = $model->exec($sql);
                exit(sprintf('处理离职信息,修改 %d 条记录',$num));
            }
        }

        $update_str = '';
        foreach ($arg as $key => $value)
        {
            if ($key=='操作' || $key=='人员' || $key=='生效日期') continue;
            if ($value['更改标识'] == '0') continue;

            if ($update_str != '')
            {
                $update_str = $update_str . ',';
            }
            $update_str = $update_str . $key;
        }

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_workid = $session->get('user_workid');

        //增加新记录
        $fld_str ='姓名,身份证号,手机号码,属地,招聘渠道,' .
            '员工类别,实习结束日期,部门编码,部门名称,班组,小组,' .
            '岗位名称,岗位类型,结算类型,' .
            '工号1,工号2,' .
            '培训信息,培训开始日期,培训完成日期,' .
            '一阶段日期,二阶段日期,员工阶段,员工状态,' .
            '离职日期,离职原因,派遣公司,记录开始日期,' .
            '操作来源,操作人员,
            开始操作时间,
            校验标识,删除标识';
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
                case '操作记录':
                    $col = '"更新插入" as 操作记录';
                    break;
                case '操作来源':
                    $col = '"页面" as 操作来源';
                    break;
                case '操作人员':
                    $col = sprintf('"%s" as 操作人员', $user_workid);
                    break;
                case '开始操作时间':
                    $col = date('Y-m-d H:i:s');
                    break;
            }

            foreach ($arg as $key => $value)
            {
                if ($key=='操作' || $key=='人员' || $key=='生效日期') continue;
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
            insert into ee_onjob (%s,有效标识)
            select %s,"1" as 有效标识
            from ee_onjob
            where GUID in (%s)', $fld_str, $col_str, $guid_str);

        // 原记录更新
        $sql_update = sprintf('
            update ee_onjob
            set 操作记录="更新,%s",记录结束日期="%s",有效标识="0"
            where GUID in (%s)',
            $update_str, $arg['生效日期']['值'], $guid_str);

        // 写日志
        $model->sql_log('页面修改', $menu_id, sprintf('表名=ee_onjob,GUID=%s',str_replace('"','',$guid_str)));

        $num = $model->exec($sql_insert);
        $num = $model->exec($sql_update);

        exit(sprintf('修改成功,修改 %d 条记录',$num));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 删除信息
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function delete_row($menu_id='', $type='')
    {
        $arg = $this->request->getJSON(true);

        $model = new Mcommon();

        $guid_str = '';
        foreach ($arg['人员'] as $guid)
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
            update ee_onjob
            set 操作记录="删除",记录结束日期="%s",
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
