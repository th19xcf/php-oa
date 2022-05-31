<?php
/* v1.1.1.1.202205311600, from office */

namespace App\Controllers;
use \CodeIgniter\Controller;
use App\Models\Mcommon;

class Scheduling extends Controller
{
    public function __construct()
    {
        helper(['form', 'url']);
    }
 
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 排班
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function init($menu_id='')
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
            $ee_arr['id'] = sprintf('3级^%s_%s', $row->姓名, $row->工号);
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
                    $day_arr['id'] = sprintf('%d-%d-%d', $year, $i, $j);
                    $day_arr['value'] = sprintf('%d日', $j);
                    array_push($month_arr['items'], $day_arr);
                }
                array_push($year_arr['items'], $month_arr);
            }

            array_push($date_arr, $year_arr);
        }

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
            select 业务,班务编号,班务名称,班务时段,班务小时数 
            from biz_duty_rule
            group by 业务,班务编号');

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
                $biz_arr['id'] = $row->业务;
                $biz_arr['value'] = $row->业务;
                $biz_arr['items'] = [];
            }

            $sch_arr = array();
            $sch_arr['id'] = $row->班务编号;
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
                $biz_arr['id'] = $row->业务;
                $biz_arr['value'] = $row->业务;
                $biz_arr['items'] = [];
                array_push($biz_arr['items'], $sch_arr);
            }
            if ($biz_last != $row->业务 && $ii == (count($results)-1))
            {
                array_push($duty_arr, $biz_arr);

                $biz_last = $row->业务;
                $biz_arr['id'] = $row->业务;
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
    // 排班设置
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function set($menu_id='')
    {
        $csr_arr = $this->request->getJSON(true);
    }
}