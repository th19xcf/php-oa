<?php
/* v1.4.2.1.202212191615, from home */

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
        $user_location = $session->get('user_location');

        $sql = sprintf('
            select GUID,姓名,身份证号,手机号码,
                if(instr(培训状态,"在培"),"在培",培训状态) as 培训状态,培训批次,
                concat("培训师_",if(培训老师="","待补充",培训老师)) as 培训老师,
                培训开始日期,预计完成日期,培训完成日期,
                培训离开日期,培训离开原因
            from ee_train
            where 属地="%s"
            order by if(instr(培训状态,"在培"),"在培",培训状态),
                培训老师,培训开始日期,convert(姓名 using gbk)',
            $user_location);

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
                $up1_arr[$up1_id]['value'] = '培训开始日期' . $row->培训开始日期;
                $up1_arr[$up1_id]['items'] = [];
            }
            $up1_arr[$up1_id]['num'] = count($up1_arr[$up1_id]['items'])+1;
            $up1_arr[$up1_id]['value'] = sprintf('培训开始日期%s (%d人)', 
                $row->培训开始日期, $up1_arr[$up1_id]['num']);
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
        $value = '';

        $object_arr['培训业务'] = [];
        $object_arr['培训业务'][0] = '';

        switch ($user_location)
        {
            case '北京总公司':
                $value = '北京培训业务';
                break;
            case '河北分公司':
                $value = '北一培训业务';
                break;
            case '四川分公司':
                $value = '南一培训业务';
                break;
        }

        $sql = sprintf('
            select 对象值 
            from def_object 
            where 对象名称="%s"
            order by 对象值', $value);

        $query = $model->select($sql);
        $result = $query->getResult();
        foreach($result as $val)
        {
            array_push($object_arr['培训业务'], $val->对象值);
        }

        $send['func_id'] = $menu_id;
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

        $arr = explode('^', $arg);

        // 读出数据
        $model = new Mcommon();
        $rows_arr = [];

        if ($arr[0] == '人员')
        {
            $sql = sprintf('
                select GUID,姓名,身份证号,手机号码,
                    培训业务,培训状态,培训批次,培训老师,
                    培训开始日期,预计完成日期
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
        }
        else
        {
            array_push($rows_arr, array('表项'=>'属性', '值'=>''));
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

        $arg['录入时间'] = date('Y-m-d H:m:s');
        $arg['录入人'] = $user_workid;

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

        $num = $model->exec($sql);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 新增培训人员信息
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function insert($menu_id='', $type='')
    {
        $arg = $this->request->getJSON(true);

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
            insert into ee_onjob (%s) values (%s)',
            $flds_str, $values_str);

        $num = $model->exec($sql);
        $this->json_data(200, sprintf('%d条',$num), 0);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 更新参培状态
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function tran($menu_id='', $type='')
    {
        $arg = $this->request->getJSON(true);

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_workid = $session->get('user_workid');

        $arg['结束操作时间'] = date('Y-m-d H:m:s');
        $arg['录入时间'] = date('Y-m-d H:m:s');
        $arg['录入人'] = $user_workid;

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
                select 姓名,身份证号
                from ee_onjob
                where 删除标识=""
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
                    array_push($err_arr, $err['身份证号']);
                }
                $this->json_data(400, sprintf('未执行,在人员表中有重复记录,请确认,身份证号{%s}', implode(',', $err_arr)), 0);
                return;
            }
        }

        // 修改ee_train信息
        $set_str = '';
        foreach ($arg as $key => $value)
        {
            if ($key=='操作' || $key=='人员' || $key=='生效日期' || $value=='') continue;

            if ($set_str != '') $set_str = $set_str . ',';
            $set_str = $set_str . sprintf('%s="%s"', $key, $value);
        }

        $sql = sprintf('
            update ee_train
            set %s where GUID in (%s) ',
            $set_str, $guid_str);

        $num = $model->exec($sql);

        if ($arg['培训状态'] != '通过')
        {
            $this->json_data(200, sprintf('%d条',$num), 0);
            return;
        }

        // 培训通过,记录导入人员表ee_onjob
        $arg['开始操作时间'] = date('Y-m-d H:m:s');
        $arg['结束操作时间'] = '';
    
        $sql = sprintf('
            insert into ee_onjob (
                姓名,身份证号,手机号码,属地,
                招聘渠道,
                员工类别,
                实习结束日期,
                部门编码,部门名称,班组,
                岗位名称,岗位类型,
                工号1,工号2,
                培训信息,培训开始日期,培训完成日期,
                一阶段日期,二阶段日期,
                三阶段日期,四阶段日期,
                正式期日期,
                员工阶段,员工状态,
                离职日期,离职原因,
                派遣公司,变更表项,
                记录开始日期,记录结束日期,
                录入来源,录入人)
            select 
                t1.姓名,t1.身份证号,t1.手机号码,t1.属地,
                t2.招聘渠道,
                if(t2.招聘渠道="校招","未毕业学生","合同制员工") as 员工类别,
                t2.实习结束日期,
                "" as 部门编码,"" as 部门名称,"" as 班组,
                "客服代表" as 岗位名称,"按量结算" as 岗位类型,
                "" as 工号1,"" as 工号2,
                "有" as 培训信息,培训开始日期,培训完成日期,
                培训完成日期 as 一阶段日期,"" as 二阶段日期,
                "" as 三阶段日期,"" as 四阶段日期,
                "" as 正式期日期,
                "新人组" as 员工阶段,"在职" as 员工状态,
                "" as 离职日期,"" as 离职原因,
                "" as 派遣公司,"" as 变更表项,
                "%s" as 记录开始日期,"" as 记录结束日期,
                "培训表转入" as 录入来源,"%s" as 录入人
            from
            (
                select GUID,姓名,身份证号,手机号码,属地,培训业务,培训状态,
                    培训批次,培训老师,培训开始日期,预计完成日期,
                    培训完成日期,培训离开日期,培训离开原因,面试信息,
                    开始操作时间,结束操作时间,录入来源,录入时间,录入人
                from ee_train
            ) as t1
            left join
            (
                select 姓名,身份证号,招聘渠道,实习结束日期
                from ee_interview
                group by 身份证号
            ) as t2
            on t1.身份证号=t2.身份证号
            where t1.GUID in (%s)',
            $arg['培训结束日期'], $user_workid, $guid_str);

        $num = $model->exec($sql);
        $this->json_data(200, sprintf('%d条',$num), 0);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 自定义函数
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function json_data($status=200, $msg='', $count=0)
    {
        $res = [
            'status' => $status,
            'msg' => $msg,
            'number' => $count
        ];

        echo json_encode($res);
        die;
    }
}
