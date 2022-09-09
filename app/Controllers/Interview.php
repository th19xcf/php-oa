<?php
/* v1.2.1.0.202209100025, from surface */

namespace App\Controllers;
use \CodeIgniter\Controller;
use App\Models\Mcommon;

class Interview extends Controller
{
    public function __construct()
    {
        helper(['form', 'url']);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 面试人员数据维护
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function init($menu_id='')
    {
        $model = new Mcommon();

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_location = $session->get('user_location');

        $sql = sprintf('
            select GUID,姓名,身份证号,手机号码,招聘渠道,
                if(mod(substr(身份证号,17,1),2)=0,"女","男") as 性别,
                一次面试结果 as 面试结果,
                if(参培信息="","待参培",参培信息) as 参培信息,
                一次面试日期 as 面试日期,预约培训日期
            from ee_interview
            where 属地="%s"
            order by 面试结果,参培信息,招聘渠道,预约培训日期,姓名',
            $user_location);

        $query = $model->select($sql);
        $results = $query->getResult();

        $up4_arr = []; // 面试结果
        $up3_arr = []; // 参培信息
        $up2_arr = []; // 预约培训日期
        $up1_arr = []; // 招聘渠道

        // 招聘渠道
        foreach ($results as $row)
        {
            $ee_arr = [];
            $ee_arr['id'] = sprintf('人员^%s^%s', $row->GUID, $row->姓名);
            $ee_arr['value'] = sprintf('%s (%s)', $row->姓名, $row->面试日期);

            $up1_id = sprintf('招聘渠道^%s^%s^%s^%s', $row->面试结果, $row->参培信息, $row->预约培训日期, $row->招聘渠道);
            if (array_key_exists($up1_id, $up1_arr) == false)
            {
                $up1_arr[$up1_id] = [];
                $up1_arr[$up1_id]['num'] = 0;
                $up1_arr[$up1_id]['id'] = $up1_id;
                $up1_arr[$up1_id]['value'] = $row->招聘渠道;
                $up1_arr[$up1_id]['items'] = [];
            }
            $up1_arr[$up1_id]['num'] = count($up1_arr[$up1_id]['items'])+1;
            $up1_arr[$up1_id]['value'] = sprintf('%s (%d人)', $row->招聘渠道, $up1_arr[$up1_id]['num']);
            array_push($up1_arr[$up1_id]['items'], $ee_arr);
        }

        // 预约培训日期
        foreach ($up1_arr as $up1)
        {
            $arr = explode('^', $up1['id']);
            $up2_id = sprintf('培训日期^%s^%s^%s', $arr[1], $arr[2], $arr[3]);
            if (array_key_exists($up2_id, $up2_arr) == false)
            {
                $up2_arr[$up2_id]['id'] = $up2_id;
                $up2_arr[$up2_id]['num'] = 0;
                $up2_arr[$up2_id]['value'] = '预约培训日期 ' . $arr[3];
                $up2_arr[$up2_id]['items'] = [];
            }

            $up2_arr[$up2_id]['num'] += $up1['num'];
            $up2_arr[$up2_id]['value'] = sprintf('预约培训日期 %s (%d人)', $arr[3], $up2_arr[$up2_id]['num']);
            array_push($up2_arr[$up2_id]['items'], $up1);
        }

        // 参培信息
        foreach ($up2_arr as $up2)
        {
            $arr = explode('^', $up2['id']);
            $up3_id = sprintf('参培信息^%s^%s', $arr[1], $arr[2]);
            if (array_key_exists($up3_id, $up3_arr) == false)
            {
                $up3_arr[$up3_id]['id'] = $up3_id;
                $up3_arr[$up3_id]['num'] = 0;
                $up3_arr[$up3_id]['value'] = $arr[2];
                $up3_arr[$up3_id]['items'] = [];
            }

            $up3_arr[$up3_id]['num'] += $up2['num'];
            $up3_arr[$up3_id]['value'] = sprintf('%s (%d人)', $arr[2], $up3_arr[$up3_id]['num']);
            array_push($up3_arr[$up3_id]['items'], $up2);
        }

        // 面试结果
        foreach ($up3_arr as $up3)
        {
            $arr = explode('^', $up3['id']);
            $up4_id = sprintf('面试结果^%s', $arr[1]);
            if (array_key_exists($up4_id, $up4_arr) == false)
            {
                $up4_arr[$up4_id]['id'] = $up4_id;
                $up4_arr[$up4_id]['num'] = 0;
                $up4_arr[$up4_id]['value'] = $arr[1];
                $up4_arr[$up4_id]['items'] = [];
            }

            $up4_arr[$up4_id]['num'] += $up3['num'];
            $up4_arr[$up4_id]['value'] = sprintf('%s (%d人)', $arr[1], $up4_arr[$up4_id]['num']);
            array_push($up4_arr[$up4_id]['items'], $up3);
        }

        $csr_arr = [];
        $csr_arr['id'] = '0级^面试人员';
        $csr_arr['value'] = '面试人员';
        $csr_arr['items'] = [];
        $csr_num = 0;

        foreach ($up4_arr as $up4)
        {
            $csr_num += $up4['num'];
            $csr_arr['value'] = sprintf('面试人员 (%d人)', $csr_num);
            array_push($csr_arr['items'], $up4);
        }

        $tree_arr = [];
        array_push($tree_arr, $csr_arr);

        //grid
        $grid_arr = [];

        $send['func_id'] = $menu_id;
        $send['tree_json'] = json_encode($tree_arr);
        $send['grid_json'] = json_encode($grid_arr);
        $send['import_func_id'] = '2022';
        $send['import_func_name'] = '面试人员';

        echo view('Vinterview.php', $send);
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
                select 姓名,招聘渠道,面试业务,面试岗位,一次面试日期 as 面试日期,
                    一次面试结果 as 面试结果,预约培训日期,参培信息
                from ee_interview
                where GUID=%s', $arr[1]);
            $query = $model->select($sql);
            $results = $query->getResult();
        
            array_push($rows_arr, array('表项'=>'属性', '值'=>'人员'));
            array_push($rows_arr, array('表项'=>'姓名', '值'=>$results[0]->姓名));
            array_push($rows_arr, array('表项'=>'招聘渠道', '值'=>$results[0]->招聘渠道));
            array_push($rows_arr, array('表项'=>'面试日期', '值'=>$results[0]->面试日期));
            array_push($rows_arr, array('表项'=>'面试结果', '值'=>$results[0]->面试结果));
            array_push($rows_arr, array('表项'=>'预约培训日期', '值'=>$results[0]->预约培训日期));
            array_push($rows_arr, array('表项'=>'参培信息', '值'=>$results[0]->参培信息));
        }
        else
        {
            array_push($rows_arr, array('表项'=>'属性', '值'=>''));
        }

        exit(json_encode($rows_arr));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 修改面试人员信息
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

        $sql = sprintf('
            update ee_interview 
            set 参培信息="%s" 
            where GUID in (%s) ',
            $arg['参培信息'], $guid_str);

        $num = $model->exec($sql);

        // 已参培记录导入ee_train
        if ($arg['参培信息'] == '已参培')
        {
            // 从session中取出数据
            $session = \Config\Services::session();
            $user_workid = $session->get('user_workid');

            $sql = sprintf('
                insert into ee_train (姓名,身份证号,手机号码,面试信息,录入来源,录入人)
                select 姓名,身份证号,手机号码,"有" as 面试信息,
                    "面试表转入" as 录入来源, "%s" as 录入人
                from ee_interview
                where GUID in (%s)', $user_workid, $guid_str);
            $num = $model->exec($sql);
        }
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 新增面试人员信息
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
            insert into ee_interview (%s) values (%s)',
            $flds_str, $values_str);

        $num = $model->exec($sql);
    }
}
