<?php
/* v2.2.4.1.202504261425, from home */

namespace App\Controllers;
use \CodeIgniter\Controller;
use App\Models\Mcommon;

class Train extends Controller
{
    public function __construct()
    {
        helper(['form', 'url']);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 培训人员数据维护
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function init($menu_id='')
    {
        $model = new Mcommon();

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_location_authz = $session->get('user_location_authz');
        $tree_expand = $session->get('train_tree_expand');

        $sql = sprintf('
            select GUID,姓名,身份证号,手机号码,
                if(instr(培训状态,"在培"),"在培",培训状态) as 培训状态,培训批次,
                concat("培训师_",if(培训老师="","待补充",培训老师)) as 培训老师,
                培训开始日期,预计完成日期,培训完成日期,
                培训离开日期,培训离开原因
            from ee_train
            where locate(属地,"%s") and 有效标识!="0"
            order by if(instr(培训状态,"在培"),"在培",培训状态),
                培训老师,培训开始日期,convert(姓名 using gbk)',
            $user_location_authz);

        $query = $model->select($sql);
        $results = $query->getResult();

        $up3_arr = []; // 培训状态
        $up2_arr = []; // 培训老师
        $up1_arr = []; // 培训开始日期

        // 培训开始日期
        foreach ($results as $row)
        {
            $ee_arr = [];
            $ee_arr['id'] = sprintf('人员^%s^%s', $row->GUID, $row->姓名);
            $ee_arr['value'] = sprintf('%s', $row->姓名);

            $up1_id = sprintf('培训开始日期^%s^%s^培训开始日期 (%s)', 
                $row->培训状态, $row->培训老师, $row->培训开始日期);
            if (array_key_exists($up1_id, $up1_arr) == false)
            {
                $up1_arr[$up1_id] = [];
                $up1_arr[$up1_id]['num'] = 0;
                $up1_arr[$up1_id]['id'] = $up1_id;
                $up1_arr[$up1_id]['value'] = sprintf('%s 至 %s',$row->培训开始日期,$row->预计完成日期);
                $up1_arr[$up1_id]['items'] = [];
            }
            $up1_arr[$up1_id]['num'] = count($up1_arr[$up1_id]['items'])+1;
            $up1_arr[$up1_id]['value'] = sprintf('%s 至 %s (%d人)', 
                $row->培训开始日期, $row->预计完成日期, $up1_arr[$up1_id]['num']);
            array_push($up1_arr[$up1_id]['items'], $ee_arr);
        }

        // 培训老师
        foreach ($up1_arr as $up1)
        {
            $arr = explode('^', $up1['id']);
            $up2_id = sprintf('培训老师^%s^%s', $arr[1], $arr[2]);
            if (array_key_exists($up2_id, $up2_arr) == false)
            {
                $up2_arr[$up2_id]['id'] = $up2_id;
                $up2_arr[$up2_id]['num'] = 0;
                $up2_arr[$up2_id]['value'] = $arr[2];
                $up2_arr[$up2_id]['items'] = [];
            }

            $up2_arr[$up2_id]['num'] += $up1['num'];
            $up2_arr[$up2_id]['value'] = sprintf('%s (%d人)', 
                $arr[2], $up2_arr[$up2_id]['num']);
            array_push($up2_arr[$up2_id]['items'], $up1);
        }

        // 培训状态
        foreach ($up2_arr as $up2)
        {
            $arr = explode('^', $up2['id']);
            $up3_id = sprintf('培训状态^%s', $arr[1]);
            if (array_key_exists($up3_id, $up3_arr) == false)
            {
                $up3_arr[$up3_id]['id'] = $up3_id;
                $up3_arr[$up3_id]['num'] = 0;
                $up3_arr[$up3_id]['value'] = $arr[1];
                $up3_arr[$up3_id]['items'] = [];
            }

            $up3_arr[$up3_id]['num'] += $up2['num'];
            $up3_arr[$up3_id]['value'] = sprintf('%s (%d人)', 
                $arr[1], $up3_arr[$up3_id]['num']);
            array_push($up3_arr[$up3_id]['items'], $up2);
        }

        $csr_arr = [];
        $csr_arr['id'] = '0级^培训人员';
        $csr_arr['value'] = '培训人员';
        $csr_arr['items'] = [];
        $csr_num = 0;

        foreach ($up3_arr as $up3)
        {
            $csr_num += $up3['num'];
            $csr_arr['value'] = sprintf('培训人员 (%d人)', $csr_num);
            array_push($csr_arr['items'], $up3);
        }

        $tree_arr = [];
        array_push($tree_arr, $csr_arr);

        //grid
        $grid_arr = [];

        // 直接给一些固定变量赋值
        $object_arr = []; 

        $object_arr['培训业务'] = [];
        $object_arr['培训业务'][0] = '';

        $sql = sprintf('
            select 对象值 
            from def_object 
            where 对象名称="培训业务" and locate(属地,"%s")
            order by convert(对象值 using gbk)', 
            $user_location_authz);

        $query = $model->select($sql);
        $result = $query->getResult();
        foreach($result as $val)
        {
            array_push($object_arr['培训业务'], $val->对象值);
        }

        $send['func_id'] = $menu_id;
        $send['tree_expand_json'] = json_encode($tree_expand);
        $send['tree_json'] = json_encode($tree_arr);
        $send['grid_json'] = json_encode($grid_arr);
        $send['import_func_id'] = '2032';
        $send['import_func_name'] = '培训人员';
        $send['object_json'] = json_encode($object_arr);

        echo view('Vtrain.php', $send);
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
            $session_arr['train_tree_expand'] = $expand_arr;
            $session = \Config\Services::session();
            $session->set($session_arr);
            return;
        }

        $model = new Mcommon();

        $arr = explode('^', $arg['id']);
        $rows_arr = [];

        // 读出数据
        $model = new Mcommon();
        $rows_arr = [];

        if ($arr[0] == '人员')
        {
            $sql = sprintf('
                select GUID,姓名,身份证号,手机号码,
                    培训业务,培训状态,培训批次,培训老师,
                    培训开始日期,预计完成日期,
                    培训完成日期,培训离开日期,
                    培训离开原因,培训天数
                from ee_train
                where GUID=%s', $arr[1]);
            $query = $model->select($sql);
            $results = $query->getResult();

            array_push($rows_arr, array('表项'=>'属性', '值'=>'查询培训信息'));
            array_push($rows_arr, array('表项'=>'姓名', '值'=>$results[0]->姓名));
            array_push($rows_arr, array('表项'=>'培训业务', '值'=>$results[0]->培训业务));
            array_push($rows_arr, array('表项'=>'培训批次', '值'=>$results[0]->培训批次));
            array_push($rows_arr, array('表项'=>'培训老师', '值'=>$results[0]->培训老师));
            array_push($rows_arr, array('表项'=>'培训开始日期', '值'=>$results[0]->培训开始日期));
            array_push($rows_arr, array('表项'=>'预计完成日期', '值'=>$results[0]->预计完成日期));
            array_push($rows_arr, array('表项'=>'培训完成日期', '值'=>$results[0]->培训完成日期));
            array_push($rows_arr, array('表项'=>'培训离开日期', '值'=>$results[0]->培训离开日期));
            array_push($rows_arr, array('表项'=>'培训离开原因', '值'=>$results[0]->培训离开原因));
            array_push($rows_arr, array('表项'=>'培训天数', '值'=>$results[0]->培训天数));
        }
        else
        {
            array_push($rows_arr, array('表项'=>'属性', '值'=>'查询培训信息 - 请选择人员'));
        }

        exit(json_encode($rows_arr));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 培训信息修改
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function upkeep($menu_id='', $type='')
    {
        $arg = $this->request->getJSON(true);

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_workid = $session->get('user_workid');

        $arg['操作来源'] = '页面修改';
        $arg['操作人员'] = $user_workid;
        $arg['操作时间'] = date('Y-m-d H:m:s');

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

        $set_str = '';
        foreach ($arg as $key => $value)
        {
            if ($key=='操作' || $key=='人员' || $value=='') continue;

            if ($set_str != '') $set_str = $set_str . ',';
            $set_str = $set_str . sprintf('%s="%s"', $key, $value);
        }

        $sql = sprintf('
            update ee_train
            set %s where GUID in (%s) ',
            $set_str, $guid_str);

        // 写日志
        $model->sql_log('页面修改', $menu_id, sprintf('表名=ee_train,GUID="%s"', $guid_str));

        $num = $model->exec($sql);
        exit(sprintf('`修改培训信息`成功,修改 %d 条记录',$num));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 新增培训信息
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function insert($menu_id='', $type='')
    {
        $arg = $this->request->getJSON(true);

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_workid = $session->get('user_workid');

        $arg['操作来源'] = '页面新增';
        $arg['操作人员'] = $user_workid;
        $arg['开始操作时间'] = date('Y-m-d H:m:s');
        $arg['结束操作时间'] = '';
        $arg['操作时间'] = date('Y-m-d H:m:s');

        $model = new Mcommon();

        $flds_str = '';
        $values_str = '';
        foreach ($arg as $key => $value)
        {
            if ($key == '操作') continue;

            if ($flds_str != '')
            {
                $flds_str = $flds_str . ',';
                $values_str = $values_str . ',';
            }
            $flds_str = $flds_str . $key;
            $values_str = sprintf('%s"%s"', $values_str, $value);
        }

        $sql = sprintf('
            insert into ee_train (%s) values (%s)',
            $flds_str, $values_str);

        $num = $model->exec($sql);
        exit(sprintf('`新增培训信息`成功,新增 %d 条记录',$num));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 更新参培信息,转人员表
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function tran($menu_id='', $type='')
    {
        $arg = $this->request->getJSON(true);

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_workid = $session->get('user_workid');

        $arg['结束操作时间'] = date('Y-m-d H:m:s');
        $arg['操作时间'] = date('Y-m-d H:m:s');
        $arg['操作人员'] = $user_workid;

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
                $guid_str = sprintf('%s,"%s"', $guid_str, $guid);
            }
        }

        if ($arg['培训状态'] == '通过')
        {
            // 查询ee_onjob中是否有重复记录,有则报错
            $sql = sprintf('
                select 姓名,身份证号,入职次数
                from ee_onjob
                where 有效标识="1"
                    and 删除标识!="1"
                    and 身份证号 in
                    (
                        select 身份证号
                        from ee_train
                        where GUID in (%s)
                        group by 身份证号
                    )', $guid_str);

            $errs = $model->select($sql)->getResultArray();

            if (count($errs) != 0)
            {
                $err_arr = [];
                foreach ($errs as $err)
                {
                    if ((int)$arg['入职次数'] != ($err['入职次数']+1))
                    {
                        array_push($err_arr, $err['身份证号'].'^入职次数='.$err['入职次数']);
                    }
                }
                if (count($err_arr) != 0)
                {
                    exit(sprintf('未执行,在人员表中有相关的人员记录,请设置入职次数+1,身份证号{%s}',implode(',',$err_arr)));
                    return;
                }
            }

            // 修改ee_train信息
            $sql = sprintf('
                update ee_train
                set 培训状态="%s",培训完成日期="%s",
                    结束操作时间="%s",操作时间="%s",操作人员="%s" 
                where GUID in (%s) ',
                $arg['培训状态'], $arg['培训结束日期'], 
                $arg['结束操作时间'], $arg['操作时间'], $arg['操作人员'], 
                $guid_str);

            $num = $model->exec($sql);
        }
        else
        {
            // 修改ee_train信息
            $sql = sprintf('
                update ee_train
                set 培训状态="%s",培训离开日期="%s",培训离开原因="%s",
                    结束操作时间="%s",操作时间="%s",操作人员="%s" 
                where GUID in (%s) ',
                $arg['培训状态'], $arg['培训结束日期'], $arg['培训离开原因'], 
                $arg['结束操作时间'], $arg['操作时间'], $arg['操作人员'], 
                $guid_str);

            $num = $model->exec($sql);
            exit(sprintf('更新成功,更新%d条',$num));
        }

        // 培训通过,记录导入人员表ee_onjob
        $arg['开始操作时间'] = date('Y-m-d H:m:s');
        $arg['结束操作时间'] = '';
    
        $sql = sprintf('
            insert into ee_onjob (
                姓名,身份证号,手机号码,属地,入职次数,
                招聘渠道,
                员工类别,
                实习结束日期,
                部门编码,部门名称,班组,
                岗位名称,岗位类型,
                结算类型,
                工号1,工号2,
                培训信息,培训开始日期,培训完成日期,
                一阶段日期,二阶段日期,
                三阶段日期,四阶段日期,
                正式期日期,
                员工阶段,员工状态,
                离职日期,离职原因,
                派遣公司,
                记录开始日期,记录结束日期,
                操作来源,操作人员,
                开始操作时间,结束操作时间,
                校验标识,删除标识,有效标识)
            select 
                t1.姓名,t1.身份证号,t1.手机号码,t1.属地,%d,
                t2.招聘渠道,
                if(t2.招聘渠道="校招","未毕业学生","合同制员工") as 员工类别,
                t2.实习结束日期,
                "" as 部门编码,"" as 部门名称,"" as 班组,
                "客服代表" as 岗位名称,"%s" as 岗位类型,
                "%s" as 结算类型,
                "" as 工号1,"" as 工号2,
                "有" as 培训信息,培训开始日期,培训完成日期,
                培训完成日期 as 一阶段日期,"" as 二阶段日期,
                "" as 三阶段日期,"" as 四阶段日期,
                "" as 正式期日期,
                "新人组" as 员工阶段,"在职" as 员工状态,
                "" as 离职日期,"" as 离职原因,
                "" as 派遣公司,
                "%s" as 记录开始日期,"" as 记录结束日期,
                "培训表转入" as 操作来源,"%s" as 操作人员,
                "%s" as 开始操作时间,"" as 结束操作时间,
                "0" as 校验标识,"0" as 删除标识,"1" as 有效标识
            from
            (
                select GUID,姓名,身份证号,手机号码,属地,培训业务,培训状态,
                    培训批次,培训老师,培训开始日期,预计完成日期,
                    培训完成日期,培训离开日期,培训离开原因,面试信息,
                    开始操作时间,结束操作时间
                from ee_train
                where GUID in (%s)
            ) as t1
            left join
            (
                select 姓名,身份证号,招聘渠道,实习结束日期
                from ee_interview
                group by 身份证号
            ) as t2
            on t1.身份证号=t2.身份证号',
            (int)$arg['入职次数'],
            $arg['岗位类型'], $arg['结算类型'],
            $arg['培训结束日期'],
            $user_workid,             
            date('Y-m-d H:m:s'),
            $guid_str);

        $num = $model->exec($sql);
        exit(sprintf('`更新培训状态`成功,更新 %d 条记录',$num));
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
            update ee_train
            set 操作来源="删除",
                操作来源="页面删除",操作人员="%s",
                删除标识="1",有效标识="0"
            where GUID in (%s)',
            $user_workid, $guid_str);

        // 写日志
        $model->sql_log('删除', $menu_id, sprintf('sql=%s',str_replace('"','',$sql_update)));

        $num = $model->exec($sql_update);
    }
}
