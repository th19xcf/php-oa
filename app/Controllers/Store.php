<?php
/* v1.2.1.1.202209100025, from surface */

namespace App\Controllers;
use \CodeIgniter\Controller;
use App\Models\Mcommon;

class Store extends Controller
{
    public function __construct()
    {
        helper(['form', 'url']);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 邀约人员数据维护
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function init($menu_id='')
    {
        $model = new Mcommon();

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_location = $session->get('user_location');

        $sql = sprintf('
            select GUID,姓名,身份证号,性别,年龄,手机号码,
                学校,专业,现住址,属地,
                邀约结果,招聘渠道,信息来源,邀约日期,邀约人,
                邀约业务,邀约岗位,预约面试日期,
                if(面试信息="","待面试",面试信息) as 面试信息,
                录入来源,录入人,录入时间
            from ee_store
            where 属地="%s"
            order by 邀约结果,面试信息,预约面试日期,招聘渠道,姓名',
            $user_location);

        $query = $model->select($sql);
        $results = $query->getResult();

        $up4_arr = []; // 邀约结果
        $up3_arr = []; // 面试信息
        $up2_arr = []; // 预约面试日期
        $up1_arr = []; // 招聘渠道

        // 招聘渠道
        foreach ($results as $row)
        {
            $ee_arr = [];
            $ee_arr['id'] = sprintf('人员^%s^%s', $row->GUID, $row->姓名);
            $ee_arr['value'] = sprintf('%s (%s)', $row->姓名, $row->邀约日期);

            $up1_id = sprintf('招聘渠道^%s^%s^%s^%s', $row->邀约结果, $row->面试信息, $row->预约面试日期, $row->招聘渠道);
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

        // 预约面试日期
        foreach ($up1_arr as $up1)
        {
            $arr = explode('^', $up1['id']);
            $up2_id = sprintf('面试日期^%s^%s^%s', $arr[1], $arr[2], $arr[3]);
            if (array_key_exists($up2_id, $up2_arr) == false)
            {
                $up2_arr[$up2_id]['id'] = $up2_id;
                $up2_arr[$up2_id]['num'] = 0;
                $up2_arr[$up2_id]['value'] = '预约面试日期 ' . $arr[3];
                $up2_arr[$up2_id]['items'] = [];
            }

            $up2_arr[$up2_id]['num'] += $up1['num'];
            $up2_arr[$up2_id]['value'] = sprintf('预约面试日期 %s (%d人)', $arr[3], $up2_arr[$up2_id]['num']);
            array_push($up2_arr[$up2_id]['items'], $up1);
        }

        // 面试信息
        foreach ($up2_arr as $up2)
        {
            $arr = explode('^', $up2['id']);
            $up3_id = sprintf('面试信息^%s^%s', $arr[1], $arr[2]);
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

        // 邀约结果
        foreach ($up3_arr as $up3)
        {
            $arr = explode('^', $up3['id']);
            $up4_id = sprintf('邀约结果^%s', $arr[1]);
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
        $csr_arr['id'] = '0级^邀约人员';
        $csr_arr['value'] = '邀约人员';
        $csr_arr['items'] = [];
        $csr_num = 0;

        foreach ($up4_arr as $up4)
        {
            $csr_num += $up4['num'];
            $csr_arr['value'] = sprintf('邀约人员 (%d人)', $csr_num);
            array_push($csr_arr['items'], $up4);
        }

        $tree_arr = [];
        array_push($tree_arr, $csr_arr);

        //grid
        $grid_arr = [];

        $send['func_id'] = $menu_id;
        $send['tree_json'] = json_encode($tree_arr);
        $send['grid_json'] = json_encode($grid_arr);
        $send['import_func_id'] = '2012';
        $send['import_func_name'] = '邀约人员';

        echo view('Vstore.php', $send);
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
                select 姓名,身份证号,性别,年龄,手机号码,
                    学校,专业,现住址,属地,招聘渠道,渠道名称,信息来源,
                    邀约业务,邀约岗位,邀约日期,邀约人,
                    预约面试日期,邀约结果,面试信息
                from ee_store
                where GUID=%s', $arr[1]);
            $query = $model->select($sql);
            $results = $query->getResult();

            array_push($rows_arr, array('表项'=>'属性', '值'=>'查询邀约信息'));
            array_push($rows_arr, array('表项'=>'姓名', '值'=>$results[0]->姓名));
            array_push($rows_arr, array('表项'=>'身份证号', '值'=>$results[0]->身份证号));
            array_push($rows_arr, array('表项'=>'性别', '值'=>$results[0]->性别));
            array_push($rows_arr, array('表项'=>'年龄', '值'=>$results[0]->年龄));
            array_push($rows_arr, array('表项'=>'手机号码', '值'=>$results[0]->手机号码));
            array_push($rows_arr, array('表项'=>'学校', '值'=>$results[0]->学校));
            array_push($rows_arr, array('表项'=>'专业', '值'=>$results[0]->专业));
            array_push($rows_arr, array('表项'=>'现住址', '值'=>$results[0]->现住址));
            array_push($rows_arr, array('表项'=>'属地', '值'=>$results[0]->属地));
            array_push($rows_arr, array('表项'=>'招聘渠道', '值'=>$results[0]->招聘渠道));
            array_push($rows_arr, array('表项'=>'渠道名称', '值'=>$results[0]->渠道名称));
            array_push($rows_arr, array('表项'=>'信息来源', '值'=>$results[0]->信息来源));
            array_push($rows_arr, array('表项'=>'邀约业务', '值'=>$results[0]->邀约业务));
            array_push($rows_arr, array('表项'=>'邀约岗位', '值'=>$results[0]->邀约岗位));
            array_push($rows_arr, array('表项'=>'邀约日期', '值'=>$results[0]->邀约日期));
            array_push($rows_arr, array('表项'=>'邀约人', '值'=>$results[0]->邀约人));
            array_push($rows_arr, array('表项'=>'预约面试日期', '值'=>$results[0]->预约面试日期));
            array_push($rows_arr, array('表项'=>'邀约结果', '值'=>$results[0]->邀约结果));
            #array_push($rows_arr, array('表项'=>'面试信息', '值'=>$results[0]->面试信息));
        }
        else
        {
            array_push($rows_arr, array('表项'=>'属性', '值'=>''));
        }

        exit(json_encode($rows_arr));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 修改邀约人员信息
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
                $guid_str = sprintf('%s,"%s"', $guid_str, $guid);
            }
        }

        $sql = sprintf('
            update ee_store 
            set 姓名="%s",身份证号="%s",性别="%s",年龄=%d,
                手机号码="%s",学校="%s",专业="%s",现住址="%s",
                招聘渠道="%s",渠道名称="%s",信息来源="%s"
            where GUID in (%s) ',
            $arg['姓名'],$arg['身份证号'],$arg['性别'],$arg['年龄'],
            $arg['手机号码'],$arg['学校'],$arg['专业'],$arg['现住址'],
            $arg['招聘渠道'],$arg['渠道名称'],$arg['信息来源'],
            $guid_str);

        $num = $model->exec($sql);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 新增邀约人员信息
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
            insert into ee_store (%s) values (%s)',
            $flds_str, $values_str);

        $num = $model->exec($sql);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 转面试
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function tran($menu_id='', $type='')
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
                $guid_str = sprintf('%s,"%s"', $guid_str, $guid);
            }
        }

        $sql = sprintf('
            update ee_store
            set 面试信息="%s" 
            where GUID in (%s) ',
            $arg['面试信息'], $guid_str);

        $num = $model->exec($sql);

        // 邀约记录导入面试表ee_interview
        if ($arg['面试信息'] == '转面试')
        {
            // 从session中取出数据
            $session = \Config\Services::session();
            $user_workid = $session->get('user_workid');

            $sql = sprintf('
                insert into ee_interview (姓名,身份证号,手机号码,属地,
                    招聘渠道,渠道名称,信息来源,实习结束日期,
                    面试业务,面试岗位,一次面试日期,一次面试人,一次面试结果,
                    预约培训日期,邀约信息,录入来源,录入人)
                select 姓名,身份证号,手机号码,属地,
                    招聘渠道,渠道名称,信息来源,"",
                    邀约业务,邀约岗位,"","","",
                    "","通过","邀约表转入" as 录入来源,"%s" as 录入人
                from ee_store
                where GUID in (%s)', $user_workid, $guid_str);
            $num = $model->exec($sql);
        }
    }
}
