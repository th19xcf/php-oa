<?php
/* v1.1.7.1.202207281030, from home */

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
    //入职人员
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function init($menu_id='')
    {
        $model = new Mcommon();

        $sql = sprintf('
            select GUID,姓名,工号1 as 工号,员工状态,
                部门名称,
                if(班组="","未分班组",班组) as 班组,
                岗位名称,岗位类型,
                培训完成日期
            from ee_onjob
            where 变更表项=""
            order by 员工状态,部门名称,班组,姓名');

        $query = $model->select($sql);
        $results = $query->getResult();

        $state_arr = [];
        $dept_arr = [];
        $team_arr = [];

        $team_ee = 0;
        foreach ($results as $row)
        {
            $ee_arr = [];
            $ee_arr['id'] = sprintf('EE^%s^%s', $row->GUID, $row->姓名);
            $ee_arr['value'] = sprintf('%s (工号%s) (培训完成%s)', $row->姓名, $row->工号,$row->培训完成日期);

            $team_id = sprintf('TEAM^%s^%s^%s', $row->员工状态, $row->部门名称, $row->班组);
            if (array_key_exists($team_id, $team_arr) == false)
            {
                $team_arr[$team_id] = [];
                $team_arr[$team_id]['num'] = 0;
                $team_arr[$team_id]['id'] = $team_id;
                $team_arr[$team_id]['value'] = $row->班组;
                $team_arr[$team_id]['items'] = [];
            }
            $team_arr[$team_id]['num'] = count($team_arr[$team_id]['items'])+1;
            $team_arr[$team_id]['value'] = sprintf('%s (%d人)', $row->班组, $team_arr[$team_id]['num']);
            array_push($team_arr[$team_id]['items'], $ee_arr);
        }

        foreach ($team_arr as $team)
        {
            $arr = explode('^', $team['id']);
            $dept_id = sprintf('DEPT^%s^%s', $arr[1], $arr[2]);
            if (array_key_exists($dept_id, $dept_arr) == false)
            {
                $dept_arr[$dept_id]['id'] = $dept_id;
                $dept_arr[$dept_id]['num'] = 0;
                $dept_arr[$dept_id]['value'] = $arr[2];
                $dept_arr[$dept_id]['items'] = [];
            }

            $dept_arr[$dept_id]['num'] += $team['num'];
            $dept_arr[$dept_id]['value'] = sprintf('%s (%d人)', $arr[2], $dept_arr[$dept_id]['num']);
            array_push($dept_arr[$dept_id]['items'], $team);
        }

        foreach ($dept_arr as $dept)
        {
            $arr = explode('^', $dept['id']);
            $state_id = sprintf('1级^%s', $arr[1]);
            if (array_key_exists($state_id, $state_arr) == false)
            {
                $state_arr[$state_id]['id'] = $state_id;
                $state_arr[$state_id]['num'] = 0;
                $state_arr[$state_id]['value'] = $arr[1];
                $state_arr[$state_id]['items'] = [];
            }

            $state_arr[$state_id]['num'] += $dept['num'];
            $state_arr[$state_id]['value'] = sprintf('%s (%d人)', $arr[1], $state_arr[$state_id]['num']);
            array_push($state_arr[$state_id]['items'], $dept);
        }

        $csr_arr = [];
        $csr_arr['id'] = '0级^入职人员';
        $csr_arr['value'] = '入职人员';
        $csr_arr['items'] = [];
        $csr_num = 0;

        foreach ($state_arr as $state)
        {
            $csr_num += $state['num'];
            $csr_arr['value'] = sprintf('入职人员 (%d人)', $csr_num);
            array_push($csr_arr['items'], $state);
        }

        $tree_arr = [];
        array_push($tree_arr, $csr_arr);

        //grid
        $grid_arr = [];
        /*
        array_push($grid_arr, array('表项'=>'姓名', '值'=>''));
        array_push($grid_arr, array('表项'=>'员工状态', '值'=>''));
        array_push($grid_arr, array('表项'=>'岗位名称', '值'=>''));
        array_push($grid_arr, array('表项'=>'岗位类型', '值'=>''));
        array_push($grid_arr, array('表项'=>'部门名称', '值'=>''));
        array_push($grid_arr, array('表项'=>'班组', '值'=>''));
        array_push($grid_arr, array('表项'=>'离职日期', '值'=>''));
        array_push($grid_arr, array('表项'=>'离职原因', '值'=>''));
        */

        $send['func_id'] = $menu_id;
        $send['tree_json'] = json_encode($tree_arr);
        $send['grid_json'] = json_encode($grid_arr);
        $send['import_func_id'] = '2042';
        $send['import_func_name'] = '入职人员';

        echo view('VEmployee.php', $send);
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

        if ($arr[0] == 'DEPT')
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
        else if ($arr[0] == 'TEAM')
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
        else if ($arr[0] == 'EE')
        {
            $sql = sprintf('
                select 姓名,身份证号,员工状态,岗位名称,岗位类型,部门名称,班组,离职日期,离职原因
                from ee_onjob
                where GUID=%s', $arr[1]);
            $query = $model->select($sql);
            $results = $query->getResult();
        
            array_push($rows_arr, array('表项'=>'属性', '值'=>'人员'));
            array_push($rows_arr, array('表项'=>'姓名', '值'=>$results[0]->姓名));
            array_push($rows_arr, array('表项'=>'岗位名称', '值'=>$results[0]->岗位名称));
            array_push($rows_arr, array('表项'=>'岗位类型', '值'=>$results[0]->岗位类型));
            array_push($rows_arr, array('表项'=>'部门名称', '值'=>$results[0]->部门名称));
            array_push($rows_arr, array('表项'=>'班组', '值'=>$results[0]->班组));
            array_push($rows_arr, array('表项'=>'员工状态', '值'=>$results[0]->员工状态));
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

        if ($arg['员工状态'] == '离职') //更新所有该员工的记录
        {
            $sql = sprintf('
                update ee_onjob
                set 员工状态="%s",离职日期="%s",离职原因="%s"
                where 身份证号 in
                    (
                        select 身份证号
                        from
                        (
                            select 身份证号
                            from ee_onjob
                            where GUID in (%s)
                        ) as ta
                    )',
                $arg['员工状态'], $arg['离职日期'], $arg['离职原因'], $guid_str);

            $num = $model->exec($sql);
        }
        else
        {
            $update_str = '';
            foreach ($arg as $key => $value)
            {
                if ($key=='操作' || $key=='人员' || $key=='生效日期' || $value=='') continue;

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
            $fld_str ='姓名,身份证号,手机号码,招聘渠道,' .
                '员工类别,部门编码,部门名称,班组,岗位名称,岗位类型,' .
                '工号1,工号2,实习结束日期,培训信息,培训开始日期,' .
                '培训完成日期,一阶段日期,二阶段日期,员工状态,员工阶段,' .
                '离职日期,离职原因,派遣公司,记录开始日期,' .
                '录入来源,录入人';
            $fld_arr = explode(',', $fld_str);

            $col_str = '';
            foreach ($fld_arr as $fld)
            {
                $col = $fld;

                switch ($fld)
                {
                    case '记录开始日期':
                        $col = sprintf('"%s" as 记录开始日期', $arg['生效日期']);
                        break;
                    case '录入来源':
                        $col = '"页面更改" as 录入来源';
                        break;
                    case '录入人':
                        $col = sprintf('"%s" as 录入来源', $user_workid);
                        break;
                }

                foreach ($arg as $key => $value)
                {
                    if ($value == '') continue;
                    if ($fld == $key)
                    {
                        $col = sprintf('"%s" as %s', $value, $key);
                        break;
                    }
                }
                if ($col_str != '') $col_str = $col_str . ',';
                $col_str = $col_str . $col;
            }

            $sql_insert = sprintf('
                insert into ee_onjob (%s)
                select %s from ee_onjob
                where GUID in (%s)', $fld_str, $col_str, $guid_str);

            //原记录更新
            $sql_update = sprintf('
                update ee_onjob
                set 变更表项="%s",记录结束日期="%s"
                where GUID in (%s)',
                $update_str, $arg['生效日期'], $guid_str);

                $num = $model->exec($sql_insert);
                $num = $model->exec($sql_update);
        }
    }
}
