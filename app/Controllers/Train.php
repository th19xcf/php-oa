<?php
/* v1.1.2.1.202207121755, from office*/

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

        $sql = sprintf('
            select GUID,姓名,身份证号,手机号码,培训状态,培训批次,
                concat("培训师_",if(培训老师="","待补充",培训老师)) as 培训老师,
                培训开始日期,预计完成日期,培训完成日期,
                培训离开日期,培训离开原因
            from ee_train
            order by 培训状态,培训老师,培训开始日期,姓名');

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

            $up1_id = sprintf('培训开始日期^%s^%s^培训开始日期 (%s)', $row->培训状态, $row->培训老师, $row->培训开始日期);
            if (array_key_exists($up1_id, $up1_arr) == false)
            {
                $up1_arr[$up1_id] = [];
                $up1_arr[$up1_id]['num'] = 0;
                $up1_arr[$up1_id]['id'] = $up1_id;
                $up1_arr[$up1_id]['value'] = '培训开始日期' . $row->培训开始日期;
                $up1_arr[$up1_id]['items'] = [];
            }
            $up1_arr[$up1_id]['num'] = count($up1_arr[$up1_id]['items'])+1;
            $up1_arr[$up1_id]['value'] = sprintf('培训开始日期%s (%d人)', $row->培训开始日期, $up1_arr[$up1_id]['num']);
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
            $up2_arr[$up2_id]['value'] = sprintf('%s (%d人)', $arr[2], $up2_arr[$up2_id]['num']);
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
            $up3_arr[$up3_id]['value'] = sprintf('%s (%d人)', $arr[1], $up3_arr[$up3_id]['num']);
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

        $send['func_id'] = $menu_id;
        $send['tree_json'] = json_encode($tree_arr);
        $send['grid_json'] = json_encode($grid_arr);
        $send['import_func_id'] = '2032';
        $send['import_func_name'] = '培训人员';

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
                    培训业务,培训状态,培训业务,培训批次,培训老师,
                    培训开始日期,预计完成日期,培训完成日期,
                    培训离开日期,培训离开原因
                from ee_train
                where GUID=%s', $arr[1]);
            $query = $model->select($sql);
            $results = $query->getResult();
        
            array_push($rows_arr, array('表项'=>'属性', '值'=>'人员'));
            array_push($rows_arr, array('表项'=>'姓名', '值'=>$results[0]->姓名));
            array_push($rows_arr, array('表项'=>'培训状态', '值'=>$results[0]->培训状态));
            array_push($rows_arr, array('表项'=>'培训业务', '值'=>$results[0]->培训业务));
            array_push($rows_arr, array('表项'=>'培训批次', '值'=>$results[0]->培训批次));
            array_push($rows_arr, array('表项'=>'培训老师', '值'=>$results[0]->培训老师));
            array_push($rows_arr, array('表项'=>'培训开始日期', '值'=>$results[0]->培训开始日期));
            array_push($rows_arr, array('表项'=>'预计完成日期', '值'=>$results[0]->预计完成日期));
            array_push($rows_arr, array('表项'=>'培训完成日期', '值'=>$results[0]->培训完成日期));
            array_push($rows_arr, array('表项'=>'培训离开日期', '值'=>$results[0]->培训离开日期));
            array_push($rows_arr, array('表项'=>'培训离开原因', '值'=>$results[0]->培训离开原因));
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

        if ($arg['培训状态'] == '在培')
        {
            $arg['开始操作时间'] = date('Y-m-d H:m:s');
        }
        else if ($arg['培训状态'] == '通过')
        {
            $arg['结束操作时间'] = date('Y-m-d H:m:s');
        }

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

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_workid = $session->get('user_workid');

        $sql = sprintf('
            update ee_train
            set %s where GUID in (%s) ',
            $set_str, $guid_str);

        $num = $model->exec($sql);

        // 培训通过记录导入ee_onjob
        if ($arg['培训状态'] == '通过')
        {
            // 从session中取出数据
            $session = \Config\Services::session();
            $user_workid = $session->get('user_workid');

            $sql = sprintf('
                insert into ee_onjob (姓名,身份证号,手机号码,培训开始日期,培训完成日期,录入来源,录入人)
                select 姓名,身份证号,手机号码,培训开始日期,培训完成日期,
                    "培训表转入" as 录入来源, "%s" as 录入人
                from ee_train
                where GUID in (%s)', $user_workid, $guid_str);

            $num = $model->exec($sql);
        }
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
            insert into ee_train (%s) values (%s)',
            $flds_str, $values_str);

        $num = $model->exec($sql);
    }

}
