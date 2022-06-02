<?php
/* v1.2.1.1.202206021225, from home */

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

        $sql = sprintf('select 业务,班组,姓名,工号 from view_排班人员');

        $query = $model->select($sql);
        $results = $query->getResult();

        $csr_arr = array();

        $biz_last = '';
        $biz_arr = [];
        $biz_ee_num = 0;
        $team_last = '';
        $team_arr = [];
        $team_ee_num = 0;

        $ii = 0;
        foreach ($results as $row)
        {
            $ii ++;

            if ($team_last == '')
            {
                $team_last = $row->班组;
                $team_arr['id'] = '2级^' . $row->班组;
                $team_arr['value'] = $row->班组;
                $team_arr['items'] = [];

                $biz_last = $row->业务;
                $biz_arr['id'] = '1级^' . $row->业务;
                $biz_arr['value'] = $row->业务;
                $biz_arr['items'] = [];
            }

            $ee_arr = array();
            $ee_arr['id'] = sprintf('3级^%s^%s^%s^%s', $row->业务, $row->班组, $row->姓名, $row->工号);
            $ee_arr['value'] = sprintf('%s (%s)', $row->姓名, $row->工号);

            if ($team_last == $row->班组 && $ii < (count($results)-1))
            {
                array_push($team_arr['items'], $ee_arr);
                $team_ee_num ++;
            }
            if ($team_last != $row->班组 && $ii < (count($results)-1))
            {
                // 新班组,老班组信息保存
                $team_arr['value'] = sprintf('%s  (%d人)', $team_arr['value'], $team_ee_num);
                $biz_ee_num = $biz_ee_num + $team_ee_num;
                $team_ee_num = 0;

                if ($biz_last == $row->业务)
                {
                    array_push($biz_arr['items'], $team_arr);
                }
                else
                {
                    array_push($biz_arr['items'], $team_arr);
                    $biz_arr['value'] = sprintf('%s  (%d人)', $biz_arr['value'], $biz_ee_num);
                    $biz_ee_num = 0;

                    array_push($csr_arr, $biz_arr);
    
                    $biz_last = $row->业务;
                    $biz_arr['id'] = '1级^' . $row->业务;
                    $biz_arr['value'] = $row->业务;
                    $biz_arr['items'] = [];    
                }

                // team信息初始化
                $team_last = $row->班组;
                $team_arr['id'] = '2级^' . $row->班组;
                $team_arr['value'] = $row->班组;
                $team_arr['items'] = [];

                array_push($team_arr['items'], $ee_arr);
                $team_ee_num ++;
            }

            if ($team_last == $row->班组 && $ii == (count($results)-1))
            {
                // 二级
                array_push($team_arr['items'], $ee_arr);
                $team_ee_num ++;

                $team_arr['value'] = sprintf('%s  (%d人)', $team_arr['value'], $team_ee_num);
                $biz_ee_num = $biz_ee_num + $team_ee_num;

                // 一级
                $biz_arr['value'] = sprintf('%s  (%d人)', $biz_arr['value'], $biz_ee_num);
                $biz_ee_num = 0;
                array_push($biz_arr['items'], $team_arr);

                // 最终
                array_push($csr_arr, $biz_arr);
            }

            if ($team_last != $row->班组 && $ii == (count($results)-1))
            {
            }
        }

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
        $job_arr['id'] = sprintf('北京热线');
        $job_arr['value'] = sprintf('北京热线');
        array_push($task_arr, $job_arr);
        $job_arr['id'] = sprintf('河北热线');
        $job_arr['value'] = sprintf('河北热线');
        array_push($task_arr, $job_arr);
        $job_arr['id'] = sprintf('内蒙古热线');
        $job_arr['value'] = sprintf('内蒙古热线');
        array_push($task_arr, $job_arr);
        $job_arr['id'] = sprintf('山西热线');
        $job_arr['value'] = sprintf('山西热线');
        array_push($task_arr, $job_arr);
        $job_arr['id'] = sprintf('吉林热线');
        $job_arr['value'] = sprintf('吉林热线');
        array_push($task_arr, $job_arr);

        // 班务
        $sql = sprintf('
            select 业务,班务编码,班务名称,班务时段,班务小时数 
            from biz_duty_rule
            group by 业务,班务编码');

        $query = $model->select($sql);
        $results = $query->getResult();

        $duty_arr = array();

        $biz_last = '';
        $biz_arr = [];

        $ii = 0;
        foreach ($results as $row)
        {
            $ii ++;

            if ($biz_last == '')
            {
                $biz_last = $row->业务;
                $biz_arr['id'] = '1级^' . $row->业务;
                $biz_arr['value'] = $row->业务;
                $biz_arr['items'] = [];
            }

            $sch_arr = array();
            $sch_arr['id'] = sprintf('2级^%s^%s^%.1f', $row->班务编码, $row->班务时段, $row->班务小时数);
            $sch_arr['value'] = sprintf('%s (%s^%.1f)', $row->班务名称, $row->班务时段, $row->班务小时数);

            if ($biz_last == $row->业务 && $ii < (count($results)-1))
            {
                array_push($biz_arr['items'], $sch_arr);
            }
            if ($biz_last == $row->业务 && $ii == (count($results)-1))
            {
                array_push($biz_arr['items'], $sch_arr);
                array_push($duty_arr, $biz_arr);
            }
            if ($biz_last != $row->业务 && $ii < (count($results)-1))
            {
                array_push($duty_arr, $biz_arr);

                $biz_last = $row->业务;
                $biz_arr['id'] = '1级^' . $row->业务;
                $biz_arr['value'] = $row->业务;
                $biz_arr['items'] = [];
                array_push($biz_arr['items'], $sch_arr);
            }
            if ($biz_last != $row->业务 && $ii == (count($results)-1))
            {
                array_push($duty_arr, $biz_arr);

                $biz_last = $row->业务;
                $biz_arr['id'] = '1级^' . $row->业务;
                $biz_arr['value'] = $row->业务;
                $biz_arr['items'] = [];
                array_push($biz_arr['items'], $sch_arr);

                array_push($duty_arr, $biz_arr);
            }
        }

        $send['func_id'] = $menu_id;
        $send['csr_json'] = json_encode($csr_arr);
        $send['date_json'] = json_encode($date_arr);
        $send['task_json'] = json_encode($task_arr);
        $send['duty_json'] = json_encode($duty_arr);

        echo view('VScheduling.php', $send);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 排班录入
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function scheduling_set($menu_id='')
    {
        $model = new Mcommon();

        // 写日志
        $model->sql_log('排班录入',$menu_id,'');

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

        $sql = sprintf('select 业务,班组,姓名,工号 from view_排班人员');

        $query = $model->select($sql);
        $results = $query->getResult();

        $csr_arr = array();

        $biz_last = '';
        $biz_arr = [];
        $biz_ee_num = 0;
        $team_last = '';
        $team_arr = [];
        $team_ee_num = 0;

        $ii = 0;
        foreach ($results as $row)
        {
            $ii ++;

            if ($team_last == '')
            {
                $team_last = $row->班组;
                $team_arr['id'] = '2级^' . $row->班组;
                $team_arr['value'] = $row->班组;
                $team_arr['items'] = [];

                $biz_last = $row->业务;
                $biz_arr['id'] = '1级^' . $row->业务;
                $biz_arr['value'] = $row->业务;
                $biz_arr['items'] = [];
            }

            $ee_arr = array();
            $ee_arr['id'] = sprintf('3级^%s^%s^%s^%s', $row->业务, $row->班组, $row->姓名, $row->工号);
            $ee_arr['value'] = sprintf('%s (%s)', $row->姓名, $row->工号);

            if ($team_last == $row->班组 && $ii < (count($results)-1))
            {
                array_push($team_arr['items'], $ee_arr);
                $team_ee_num ++;
            }
            if ($team_last != $row->班组 && $ii < (count($results)-1))
            {
                // 新班组,老班组信息保存
                $team_arr['value'] = sprintf('%s  (%d人)', $team_arr['value'], $team_ee_num);
                $biz_ee_num = $biz_ee_num + $team_ee_num;
                $team_ee_num = 0;

                if ($biz_last == $row->业务)
                {
                    array_push($biz_arr['items'], $team_arr);
                }
                else
                {
                    array_push($biz_arr['items'], $team_arr);
                    $biz_arr['value'] = sprintf('%s  (%d人)', $biz_arr['value'], $biz_ee_num);
                    $biz_ee_num = 0;

                    array_push($csr_arr, $biz_arr);
    
                    $biz_last = $row->业务;
                    $biz_arr['id'] = '1级^' . $row->业务;
                    $biz_arr['value'] = $row->业务;
                    $biz_arr['items'] = [];    
                }

                // team信息初始化
                $team_last = $row->班组;
                $team_arr['id'] = '2级^' . $row->班组;
                $team_arr['value'] = $row->班组;
                $team_arr['items'] = [];

                array_push($team_arr['items'], $ee_arr);
                $team_ee_num ++;
            }

            if ($team_last == $row->班组 && $ii == (count($results)-1))
            {
                // 二级
                array_push($team_arr['items'], $ee_arr);
                $team_ee_num ++;

                $team_arr['value'] = sprintf('%s  (%d人)', $team_arr['value'], $team_ee_num);
                $biz_ee_num = $biz_ee_num + $team_ee_num;

                // 一级
                $biz_arr['value'] = sprintf('%s  (%d人)', $biz_arr['value'], $biz_ee_num);
                $biz_ee_num = 0;
                array_push($biz_arr['items'], $team_arr);

                // 最终
                array_push($csr_arr, $biz_arr);
            }

            if ($team_last != $row->班组 && $ii == (count($results)-1))
            {
            }
        }

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
        $model->sql_log('考勤录入',$menu_id,'');

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

}