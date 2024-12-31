<?php
/* v3.5.2.0.202412312015, from home */
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
    public function init($menu_id='', $arg='')
    {
        $model = new Mcommon();

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_workid = $session->get('user_workid');
        $user_location = $session->get('user_location');
        $user_location_authz = $session->get('user_location_authz');
        $tree_expand = $session->get('store_tree_expand');

        $sql = sprintf('
            select 
                GUID,姓名,身份证号,性别,年龄,手机号码,
                学校,专业,现住址,属地,
                邀约结果,招聘渠道,信息来源,邀约日期,邀约人,
                邀约业务,邀约岗位,预约面试日期,
                if(面试信息="","待面试",面试信息) as 面试信息,
                操作来源,操作人员,操作时间
            from ee_store
            where 属地 in (%s)
            order by field(邀约结果,"通过","未邀约"),面试信息,预约面试日期,招聘渠道,convert(姓名 using gbk)',
            $user_location_authz);

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

        //////////////////////////
        // 读出列配置信息
        $sql = sprintf('
            select 功能编码,字段模块,部门编码字段,部门全称字段,属地字段,
                列名,列类型,列宽度,字段名,查询名,
                赋值类型,对象,对象名称,对象表名,缺省值,
                主键,可筛选,可汇总,可新增,可修改,不可为空,可颜色标注,
                提示条件,提示样式设置,异常条件,异常样式设置,字符转换,
                列顺序
            from view_function
            where 功能编码="%s" and 列顺序>0
            group by 列名
            order by 列顺序', $arg);

        $query = $model->select($sql);
        $results = $query->getResult();

        $update_value_arr = [];
        $add_value_arr = [];
        foreach ($results as $row)
        {
            // 前端update_grid信息
            // 主键不能更改
            if ($row->主键 == 1) continue;

            if ($row->可修改==0 && $row->可新增==0) continue;

            $value_arr = [];
            $value_arr['列名'] = $row->列名;
            $value_arr['字段名'] = $row->字段名;
            $value_arr['列类型'] = $row->列类型;
            $value_arr['是否可修改'] = ($row->可修改=='1' || $row->可修改=='2') ? '是' : '否';
            $value_arr['是否必填'] = ($row->不可为空=='1') ? '是' : '否';
            $value_arr['取值'] = '';

            if ($row->可修改 == 1 || $row->可修改 == 2)
            {
                $value_arr['是否可修改'] = ($row->可修改=='1' || $row->可修改=='2') ? $row->可修改 : '0';
                array_push($update_value_arr, $value_arr);
            }
            if ($row->可新增 == 1)
            {
                $value_arr['是否可修改'] = ($row->可新增=='1') ? '1' : '0';
                switch ($row->缺省值)
                {
                    case '$当日日期':
                        $value_arr['取值'] = date('Y-m-d');
                        break;
                    case '$属地':
                        $value_arr['取值'] = $user_location;
                        break;
                    case '$工号':
                        $value_arr['取值'] = $user_workid;
                        break;
                }
                array_push($add_value_arr, $value_arr);
            }

            if (strpos($row->赋值类型,'固定值') !== false && array_key_exists($row->对象,$object_arr) == false)
            {
                $object_arr[$row->对象] = [];

                $cond_obj_arr[$row->列名] = '';
                $update_obj_arr[$row->列名] = '';

                $obj_sql = sprintf('
                    select 对象名称,对象值,if(对象显示值="",对象值,对象显示值) as 对象显示值,
                        上级对象名称,上级对象值,if(上级对象显示值="",上级对象值,上级对象显示值) as 上级对象显示值
                    from def_object 
                    where 对象名称="%s"
                        and 有效标识="1"
                        and (属地="" or 属地 in (%s))
                    order by convert(对象值 using gbk)',
                    $row->对象, $user_location_authz);

                $qry = $model->select($obj_sql);
                $rslt = $qry->getResult();

                foreach($rslt as $vv)
                {
                    $object_arr[$vv->对象名称]['上级对象名称'] = $vv->上级对象名称;
                    if (array_key_exists($vv->上级对象值, $object_arr[$row->对象]) == false)
                    {
                        $object_arr[$row->对象][$vv->上级对象值] = [];
                        $object_arr[$row->对象][$vv->上级对象值]['对象值'] = [];
                        $object_arr[$row->对象][$vv->上级对象值]['对象显示值'] = [];
                    }

                    array_push($object_arr[$row->对象][$vv->上级对象值]['对象值'], $vv->对象值);
                    array_push($object_arr[$row->对象][$vv->上级对象值]['对象显示值'], $vv->对象显示值);
                }
            }
        }

        //////////////////////////

        //grid
        $grid_arr = [];

        // 直接给一些固定变量赋值
        $object_arr = []; 

        $object_arr['渠道名称'] = [];
        $object_arr['渠道名称'][0] = '';

        $sql = sprintf('
            select 对象值 
            from def_object 
            where 对象名称="渠道名称" and 属地 in (%s)
            order by convert(对象值 using gbk)',
            $user_location_authz);

        $query = $model->select($sql);
        $result = $query->getResult();
        foreach($result as $val)
        {
            array_push($object_arr['渠道名称'], $val->对象值);
        }

        $send['func_id'] = $menu_id;
        $send['tree_expand_json'] = json_encode($tree_expand);
        $send['tree_json'] = json_encode($tree_arr);
        $send['grid_json'] = json_encode($grid_arr);
        $send['import_func_id'] = '2012';
        $send['import_func_name'] = '邀约人员';

        $send['update_value_json'] = json_encode($update_value_arr);
        $send['add_value_json'] = json_encode($add_value_arr);
        $send['object_json'] = json_encode($object_arr);

        echo view('Vstore.php', $send);
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
            $session_arr['store_tree_expand'] = $expand_arr;
            $session = \Config\Services::session();
            $session->set($session_arr);
            return;
        }

        $model = new Mcommon();

        $arr = explode('^', $arg['id']);
        $rows_arr = [];

        if ($arr[0] == '人员')
        {
            $sql = sprintf('
                select 姓名,身份证号,性别,年龄,手机号码,
                    学校,专业,现住址,属地,
                    招聘渠道,渠道类型,渠道名称,信息来源,
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
            array_push($rows_arr, array('表项'=>'渠道类型', '值'=>$results[0]->渠道类型));
            array_push($rows_arr, array('表项'=>'渠道名称', '值'=>$results[0]->渠道名称));
            array_push($rows_arr, array('表项'=>'信息来源', '值'=>$results[0]->信息来源));
            array_push($rows_arr, array('表项'=>'邀约业务', '值'=>$results[0]->邀约业务));
            array_push($rows_arr, array('表项'=>'邀约岗位', '值'=>$results[0]->邀约岗位));
            array_push($rows_arr, array('表项'=>'邀约日期', '值'=>$results[0]->邀约日期));
            array_push($rows_arr, array('表项'=>'邀约人', '值'=>$results[0]->邀约人));
            array_push($rows_arr, array('表项'=>'预约面试日期', '值'=>$results[0]->预约面试日期));
            array_push($rows_arr, array('表项'=>'邀约结果', '值'=>$results[0]->邀约结果));
            array_push($rows_arr, array('表项'=>'面试信息', '值'=>$results[0]->面试信息));
        }
        else
        {
            array_push($rows_arr, array('表项'=>'属性', '值'=>'查询邀约信息 - 请选择人员'));
        }

        exit(json_encode($rows_arr));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 修改邀约人员信息
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function upkeep($menu_id='', $type='')
    {
        $arg = $this->request->getJSON(true);

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_workid = $session->get('user_workid');

        $model = new Mcommon();

        $arg['操作记录'] = '修改';
        $arg['操作来源'] = '页面';
        $arg['操作人员'] = $user_workid;
        $arg['操作时间'] = date('Y-m-d H:m:s');

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
            update ee_store
            set %s where GUID in (%s) ',
            $set_str, $guid_str);

        // 写日志
        $model->sql_log('页面修改', $menu_id, sprintf('表名=ee_store,GUID="%s"', $guid_str));
        // 更新
        $num = $model->exec($sql);

        exit(sprintf('`修改邀约信息`成功,修改 %d 条记录',$num));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 新增邀约人员信息
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function insert($menu_id='', $type='')
    {
        $arg = $this->request->getJSON(true);

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_workid = $session->get('user_workid');

        $model = new Mcommon();

        $arg['操作记录'] = '新增';
        $arg['操作来源'] = '页面';
        $arg['操作人员'] = $user_workid;
        $arg['开始操作时间'] = date('Y-m-d H:m:s');
        $arg['结束操作时间'] = '';
        $arg['操作时间'] = date('Y-m-d H:m:s');

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

        // 新增
        $num = $model->exec($sql);

        exit(sprintf('`新增邀约信息`成功,新增 %d 条记录',$num));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 更新面试信息,转面试表
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function tran($menu_id='', $type='')
    {
        $arg = $this->request->getJSON(true);

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_workid = $session->get('user_workid');

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

        // 查询ee_interview是否已有相同记录
        if ($arg['面试结果'] == '通过' || $arg['面试结果'] == '未通过')
        {
            $sql = sprintf('
                select 姓名,手机号码,max(面试次数) as 面试次数
                from ee_interview
                where 有效标识="1"
                    and 删除标识="0"
                    and 手机号码 in
                    (
                        select 手机号码
                        from ee_store
                        where GUID in (%s)
                        group by 手机号码
                    )
                group by 手机号码', $guid_str);

            $errs = $model->select($sql)->getResultArray();

            if (count($errs) != 0)
            {
                $err_arr = [];
                foreach ($errs as $err)
                {
                    if ((int)$arg['面试次数'] != ($err['面试次数']+1))
                    {
                        array_push($err_arr, sprintf('%s^%s^面试次数=%d',$err['姓名'],$err['手机号码'],$err['面试次数']));
                    }
                }
                if (count($err_arr) != 0)
                {
                    exit(sprintf('未执行,在面试表中有相关的人员记录,请设置面试次数+1,异常信息{%s}', implode(',', $err_arr)));
                }
            }
        }

        $interview = '';
        switch ($arg['面试结果'])
        {
            case '通过':
            case '未通过':
                $interview = '已面试';
                break;
            case '未面试':
                $interview = '未面试';
                break;
            default:
                $interview = '待面试';
                break;
        }

        $sql = sprintf('
            update ee_store
            set 面试信息="%s",
                操作记录="更新,面试信息",操作来源="页面",操作人员="%s",
                结束操作时间="%s",操作时间="%s"
            where GUID in (%s) ',
            $interview,$user_workid,
            date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $guid_str);

        $num = $model->exec($sql);

        // 面试的邀约记录导入面试表ee_interview
        if ($arg['面试结果'] == '通过' || $arg['面试结果'] == '未通过')
        {
            $sql = sprintf('
                insert into ee_interview (
                    姓名,身份证号,手机号码,属地,
                    招聘渠道,渠道类型,渠道名称,信息来源,实习结束日期,
                    面试业务,面试岗位,
                    一次面试日期,一次面试人,一次面试结果,
                    预约培训日期,邀约信息,
                    操作记录,操作来源,操作人员,开始操作时间)
                select 姓名,身份证号,手机号码,属地,
                    招聘渠道,渠道类型,渠道名称,信息来源,"" as 实习结束日期,
                    邀约业务 as 面试业务,邀约岗位 as 面试岗位,
                    "%s" as 一次面试日期,"%s" as 一次面试人,"%s" as 一次面试结果,
                    "%s" as 预约培训日期,"通过" as 邀约信息,
                    "邀约表转入" as 操作记录,
                    "页面" as 操作来源,"%s" as 操作人员,
                    "%s" as 开始操作时间
                from ee_store
                where GUID in (%s)', 
                $arg['面试日期'], $arg['面试人'], $arg['面试结果'], 
                $arg['预约培训日期'], $user_workid, 
                date('Y-m-d H:i:s'),
                $guid_str);
            $num = $model->exec($sql);
        }

        exit(sprintf('更新面试信息成功,更新 %d 条记录',$num));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 删除邀约信息
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
            update ee_store
            set 操作记录="删除",操作来源="页面",操作人员="%s",
                结束操作时间="%s",操作时间="%s",
                删除标识="1",有效标识="0"
            where GUID in (%s)',
            $user_workid, date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $guid_str);

        // 写日志
        $model->sql_log('页面删除', $menu_id, sprintf('表名=ee_store,GUID="%s"', $guid_str));
        // 删除
        $num = $model->exec($sql_update);

        exit(sprintf('删除成功,删除 %d 条记录',$num));
    }
}
