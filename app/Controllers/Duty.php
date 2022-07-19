<?php
/* v1.4.2.1.202207200035, from home */

namespace App\Controllers;
use \CodeIgniter\Controller;
use App\Models\Mcommon;

class Duty extends Controller
{
    public function __construct()
    {
        helper(['form', 'url']);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 排班
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function scheduling_init($menu_id='')
    {
        $model = new Mcommon();

        // 人员
        $csr_arr = $this->csr_tree();

        // 日期
        $date_arr = [];

        for ($year=2022; $year<=2023; $year++)
        {
            $year_arr['id'] = sprintf('%d年度', $year);
            $year_arr['value'] = sprintf('%d年度', $year);
            $year_arr['items'] = [];

            for ($i=1; $i<=12; $i++)
            {
                //$first_date = sprintf('%d-%02d-01',$year, $i);
                //$last_date = date('Y-m-d', strtotime('$first_date + 1 month -1 day'));

                $month_arr['id'] = sprintf('%d-%d', $year, $i);
                $month_arr['value'] = sprintf('%d月', $i);
                $month_arr['items'] = [];

                for ($j=1; $j<=31; $j++)
                {
                    $day_arr['id'] = sprintf('%d-%02d-%02d', $year, $i, $j);
                    $day_arr['value'] = sprintf('%d日', $j);
                    array_push($month_arr['items'], $day_arr);
                }
                array_push($year_arr['items'], $month_arr);
            }

            array_push($date_arr, $year_arr);
        }

        // 业务
        $task_arr = [];
        array_push($task_arr, array('id'=>'北京热线', 'value'=>'北京热线'));
        array_push($task_arr, array('id'=>'河北热线', 'value'=>'河北热线'));
        array_push($task_arr, array('id'=>'内蒙古热线', 'value'=>'内蒙古热线'));
        array_push($task_arr, array('id'=>'山西热线', 'value'=>'山西热线'));
        array_push($task_arr, array('id'=>'吉林热线', 'value'=>'吉林热线'));

        // 班务
        $sql = sprintf('
            select 月份,业务,班务编码,班务名称,班务时段,班务小时数 
            from biz_duty_rule
            group by 业务,班务编码
            order by 业务,班务编码');

        $query = $model->select($sql);
        $results = $query->getResult();

        $up2_arr = []; // 月份
        $up1_arr = []; // 业务

        $month = [];
        $duty_arr = [];

        // 业务
        foreach ($results as $row)
        {
            $item_arr = [];
            $item_arr['id'] = sprintf('班务^%s^%s^%.1f', $row->班务编码, $row->班务时段, $row->班务小时数);
            $item_arr['value'] = sprintf('%s (%s^%.1f)', $row->班务名称, $row->班务时段, $row->班务小时数);

            $up1_id = sprintf('业务^%s^%s', $row->月份, $row->业务);
            if (array_key_exists($up1_id, $up1_arr) == false)
            {
                $up1_arr[$up1_id] = [];
                $up1_arr[$up1_id]['num'] = 0;
                $up1_arr[$up1_id]['id'] = $up1_id;
                $up1_arr[$up1_id]['value'] = $row->业务;
                $up1_arr[$up1_id]['items'] = [];
            }
            $up1_arr[$up1_id]['num'] = count($up1_arr[$up1_id]['items'])+1;
            $up1_arr[$up1_id]['value'] = sprintf('%s (%d)', $row->业务, $up1_arr[$up1_id]['num']);
            array_push($up1_arr[$up1_id]['items'], $item_arr);
        }

        // 月份
        foreach ($up1_arr as $up1)
        {
            $item_arr = explode('^', $up1['id']);
            $up2_id = sprintf('月份^%s', $item_arr[1]);
            if (array_key_exists($up2_id, $up2_arr) == false)
            {
                $up2_arr[$up2_id]['id'] = $up2_id;
                $up2_arr[$up2_id]['num'] = 0;
                $up2_arr[$up2_id]['value'] = $item_arr[1];
                $up2_arr[$up2_id]['items'] = [];
            }

            $up2_arr[$up2_id]['num'] += $up1['num'];
            $up2_arr[$up2_id]['value'] = sprintf('%s (%d)', $item_arr[1], $up2_arr[$up2_id]['num']);
            array_push($up2_arr[$up2_id]['items'], $up1);
        }

        $up3_arr = [];
        $up3_arr['id'] = '0级^班务';
        $up3_arr['value'] = '班务';
        $up3_arr['items'] = [];
        $up3_num = 0;

        foreach ($up2_arr as $up2)
        {
            $up3_num += $up2['num'];
            $up3_arr['value'] = sprintf('每月班务 (%d)', $up3_num);
            array_push($up3_arr['items'], $up2);
        }

        $duty_arr = [];
        array_push($duty_arr, $up3_arr);

        $send['func_id'] = $menu_id;
        $send['csr_json'] = json_encode($csr_arr);
        $send['date_json'] = json_encode($date_arr);
        $send['task_json'] = json_encode($task_arr);
        $send['duty_json'] = json_encode($duty_arr);

        $send['import_func_id'] = '7012';
        $send['import_func_name'] = '排班';

        echo view('VScheduling.php', $send);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 排班录入
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function scheduling_set($menu_id='')
    {
        $model = new Mcommon();

        // 写日志
        $model->sql_log('排班录入', $menu_id, '');

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_workid = $session->get('user_workid');

        $sch_arr = $this->request->getJSON(true);

        $csr_arr = $sch_arr['csr'];
        $date_arr = $sch_arr['date'];
        $duty_arr = $sch_arr['duty'];

        foreach ($csr_arr as $csr_str)
        {
            $csr_biz = '';
            $csr_team = '';
            $csr_name = '';
            $csr_workid = '';

            $arr = explode('^', $csr_str);
            if ($arr[0] != '3级') continue;

            $csr_biz = $arr[1];
            $csr_team = $arr[2];
            $csr_name = $arr[3];
            $csr_workid = $arr[4];

            foreach ($date_arr as $date_str)
            {
                foreach ($duty_arr as $duty_str)
                {
                    $duty_biz = '';
                    $duty_id = '';
                    $duty_span = '';
                    $duty_hour = '';

                    $arr = explode('^', $duty_str);
                    if ($arr[0] == '1级')
                    {
                        $duty_biz = $arr[0];
                        continue;
                    }

                    $duty_id = $arr[1];
                    $duty_span = $arr[2];
                    $duty_hour = $arr[3];

                    // 插入班务表
                    $model = new Mcommon();

                    $sql = sprintf('
                        insert into biz_duty_day 
                        (姓名,工号,班组,业务,排班日期,班务编码,班务时段,班务小时数,录入人) 
                        values ("%s","%s","%s","%s","%s","%s","%s",%.1f,"%s")', 
                        $csr_name, $csr_workid, $csr_team,
                        $csr_biz,$date_str,
                        $duty_id,$duty_span,$duty_hour,
                        $user_workid);

                    $num = $model->exec($sql);
                }    
            }
        }
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 考勤
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function checkin_init($menu_id='')
    {
        $model = new Mcommon();

        // 人员
        $csr_arr = $this->csr_tree();

        // 日期
        $date_arr = [];

        for ($year=2022; $year<=2023; $year++)
        {
            $year_arr['id'] = sprintf('%d年度', $year);
            $year_arr['value'] = sprintf('%d年度', $year);
            $year_arr['items'] = [];

            for ($i=1; $i<=12; $i++)
            {
                //$first_date = sprintf('%d-%02d-01',$year, $i);
                //$last_date = date('Y-m-d', strtotime('$first_date + 1 month -1 day'));

                $month_arr['id'] = sprintf('%d-%d', $year, $i);
                $month_arr['value'] = sprintf('%d月', $i);
                $month_arr['items'] = [];

                for ($j=1; $j<=31; $j++)
                {
                    $day_arr['id'] = sprintf('%d-%02d-%02d', $year, $i, $j);
                    $day_arr['value'] = sprintf('%d日', $j);
                    array_push($month_arr['items'], $day_arr);
                }
                array_push($year_arr['items'], $month_arr);
            }

            array_push($date_arr, $year_arr);
        }

        $send['func_id'] = $menu_id;
        $send['csr_json'] = json_encode($csr_arr);
        $send['date_json'] = json_encode($date_arr);

        echo view('Vcheckin.php', $send);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 考勤录入
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function checkin_set($menu_id='')
    {
        $model = new Mcommon();

        // 写日志
        $model->sql_log('考勤录入', $menu_id, '');

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_workid = $session->get('user_workid');

        $json_arr = $this->request->getJSON(true);

        $csr_arr = $json_arr['csr'];
        $date_arr = $json_arr['checkin'];
        $duty_type = $json_arr['checkin']['考勤类型'];
        $duty_hour = $json_arr['checkin']['小时数'];

        foreach ($csr_arr as $csr_str)
        {
            $csr_biz = '';
            $csr_team = '';
            $csr_name = '';
            $csr_workid = '';

            $arr = explode('^', $csr_str);
            if ($arr[0] != '3级') continue;

            $csr_biz = $arr[1];
            $csr_team = $arr[2];
            $csr_name = $arr[3];
            $csr_workid = $arr[4];

            foreach ($date_arr as $date_str)
            {
                $sql = sprintf('
                    insert into biz_checkin_day 
                    (姓名,工号,班组,考勤日期,考勤类型,考勤小时数,录入人) 
                    values ("%s","%s","%s","%s","%s",%.1f,"%s")', 
                    $csr_name, $csr_workid, $csr_team,
                    $date_str,
                    $duty_type,$duty_hour,
                    $user_workid);

                $num = $model->exec($sql);
            }
        }
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 取出人员数据
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function csr_tree()
    {
        $model = new Mcommon();

        $sql = sprintf('
            select 业务,if(班组="","未分组",班组) as 班组,
                姓名,工号,记录开始日期,记录结束日期 
                from view_排班人员_202207');

        $query = $model->select($sql);
        $results = $query->getResult();

        $up3_arr = []; // 
        $up2_arr = []; // 业务
        $up1_arr = []; // 班组

        // 班组
        foreach ($results as $row)
        {
            $ee_arr = [];
            $ee_arr['id'] = sprintf('3级^%s^%s^%s^%s^%s', $row->业务, $row->班组, $row->姓名, $row->工号,$row->记录结束日期);
            $ee_arr['value'] = sprintf('%s (%s)', $row->姓名, $row->工号);

            $up1_id = sprintf('2级^%s^%s', $row->业务, $row->班组);
            if (array_key_exists($up1_id, $up1_arr) == false)
            {
                $up1_arr[$up1_id] = [];
                $up1_arr[$up1_id]['num'] = 0;
                $up1_arr[$up1_id]['id'] = $up1_id;
                $up1_arr[$up1_id]['value'] = $row->班组;
                $up1_arr[$up1_id]['items'] = [];
            }
            $up1_arr[$up1_id]['num'] = count($up1_arr[$up1_id]['items'])+1;
            $up1_arr[$up1_id]['value'] = sprintf('%s (%d人)', $row->班组, $up1_arr[$up1_id]['num']);
            array_push($up1_arr[$up1_id]['items'], $ee_arr);
        }

        // 业务
        foreach ($up1_arr as $up1)
        {
            $arr = explode('^', $up1['id']);
            $up2_id = sprintf('1级^%s', $arr[1]);
            if (array_key_exists($up2_id, $up2_arr) == false)
            {
                $up2_arr[$up2_id]['id'] = $up2_id;
                $up2_arr[$up2_id]['num'] = 0;
                $up2_arr[$up2_id]['value'] = $arr[1];
                $up2_arr[$up2_id]['items'] = [];
            }

            $up2_arr[$up2_id]['num'] += $up1['num'];
            $up2_arr[$up2_id]['value'] = sprintf('%s (%d人)', $arr[1], $up2_arr[$up2_id]['num']);
            array_push($up2_arr[$up2_id]['items'], $up1);
        }

        $csr_arr = [];
        $csr_arr['id'] = '0级^热线人员';
        $csr_arr['value'] = '面试通过人员';
        $csr_arr['items'] = [];
        $csr_num = 0;

        foreach ($up2_arr as $up2)
        {
            $csr_num += $up2['num'];
            $csr_arr['value'] = sprintf('热线人员 (%d人)', $csr_num);
            array_push($csr_arr['items'], $up2);
        }

        $tree_arr = [];
        array_push($tree_arr, $csr_arr);

        return $tree_arr;
    }
}