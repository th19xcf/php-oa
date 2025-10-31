<?php
/* v2.5.2.1.202510311630, from office */

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
    public function init($menu_id='', $arg='')
    {
        $model = new Mcommon();

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_workid = $session->get('user_workid');
        $user_location = $session->get('user_location');
        $user_location_authz = $session->get('user_location_authz');
        $tree_expand = $session->get('interview_tree_expand');
        $location_authz_cond = $session->get($menu_id.'-location_authz_cond');
        $user_debug_authz = $session->get('user_debug_authz');

        $sql = sprintf('
            select GUID,姓名,身份证号,手机号码,属地,
                if(mod(substr(身份证号,17,1),2)=0,"女","男") as 性别,
                招聘渠道,一次面试结果 as 面试结果,
                if(参培信息="","待参培",参培信息) as 参培信息,
                一次面试日期 as 面试日期,预约培训日期
            from ee_interview
            where %s and 有效标识="1" and 删除标识="0"
            order by 属地,field(面试结果,"未面试","通过","未通过"),
                field(参培信息,"待参培","已参培","未参培"),
                招聘渠道,预约培训日期 desc,convert(姓名 using gbk)',
            $location_authz_cond);

        $send_sql = str_replace('"','~~',str_replace('\'','~~',$sql));

        $query = $model->select($sql);
        $results = $query->getResult();

        $up5_arr = []; // 属地
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

            #id格式: 招聘渠道^属地^面试结果^参培信息^预约培训日期^招聘渠道
            $up1_id = sprintf('招聘渠道^%s^%s^%s^%s^%s', $row->属地, $row->面试结果, $row->参培信息, $row->预约培训日期, $row->招聘渠道);
            if (array_key_exists($up1_id, $up1_arr) == false)
            {
                $up1_arr[$up1_id] = [];
                $up1_arr[$up1_id]['num'] = 0;
                $up1_arr[$up1_id]['id'] = $up1_id;
                $up1_arr[$up1_id]['value'] = $row->招聘渠道;
                $up1_arr[$up1_id]['items'] = [];
            }
            $up1_arr[$up1_id]['num'] = count($up1_arr[$up1_id]['items']) + 1;
            $up1_arr[$up1_id]['value'] = sprintf('%s (%d人)', $row->招聘渠道, $up1_arr[$up1_id]['num']);
            array_push($up1_arr[$up1_id]['items'], $ee_arr);
        }

        // 预约培训日期
        foreach ($up1_arr as $up1)
        {
            #id格式: 培训日期^属地^面试结果^参培信息^预约培训日期
            $arr = explode('^', $up1['id']);
            $up2_id = sprintf('培训日期^%s^%s^%s^%s', $arr[1], $arr[2], $arr[3], $arr[4]);
            if (array_key_exists($up2_id, $up2_arr) == false)
            {
                $up2_arr[$up2_id]['id'] = $up2_id;
                $up2_arr[$up2_id]['num'] = 0;
                $up2_arr[$up2_id]['value'] = '预约培训日期 ' . $arr[4];
                $up2_arr[$up2_id]['items'] = [];
            }

            $up2_arr[$up2_id]['num'] += $up1['num'];
            $up2_arr[$up2_id]['value'] = sprintf('预约培训日期 %s (%d人)', $arr[4], $up2_arr[$up2_id]['num']);
            array_push($up2_arr[$up2_id]['items'], $up1);
        }

        // 参培信息
        foreach ($up2_arr as $up2)
        {
            #id格式: 参培信息^属地^面试结果^参培信息
            $arr = explode('^', $up2['id']);
            $up3_id = sprintf('参培信息^%s^%s^%s', $arr[1], $arr[2], $arr[3]);
            if (array_key_exists($up3_id, $up3_arr) == false)
            {
                $up3_arr[$up3_id]['id'] = $up3_id;
                $up3_arr[$up3_id]['num'] = 0;
                $up3_arr[$up3_id]['value'] = $arr[3];
                $up3_arr[$up3_id]['items'] = [];
            }

            $up3_arr[$up3_id]['num'] += $up2['num'];
            $up3_arr[$up3_id]['value'] = sprintf('%s (%d人)', $arr[3], $up3_arr[$up3_id]['num']);
            array_push($up3_arr[$up3_id]['items'], $up2);
        }

        // 面试结果
        foreach ($up3_arr as $up3)
        {
            #id格式: 面试结果^属地^面试结果
            $arr = explode('^', $up3['id']);
            $up4_id = sprintf('面试结果^%s^%s', $arr[1], $arr[2]);
            if (array_key_exists($up4_id, $up4_arr) == false)
            {
                $up4_arr[$up4_id]['id'] = $up4_id;
                $up4_arr[$up4_id]['num'] = 0;
                $up4_arr[$up4_id]['value'] = $arr[2];
                $up4_arr[$up4_id]['items'] = [];
            }

            $up4_arr[$up4_id]['num'] += $up3['num'];
            $up4_arr[$up4_id]['value'] = sprintf('%s (%d人)', $arr[2], $up4_arr[$up4_id]['num']);
            array_push($up4_arr[$up4_id]['items'], $up3);
        }

        // 属地
        foreach ($up4_arr as $up4)
        {
            #id格式: 属地^属地
            $arr = explode('^', $up4['id']);
            $up5_id = sprintf('属地^%s', $arr[1]);
            if (array_key_exists($up5_id, $up5_arr) == false)
            {
                $up5_arr[$up5_id]['id'] = $up5_id;
                $up5_arr[$up5_id]['num'] = 0;
                $up5_arr[$up5_id]['value'] = $arr[1];
                $up5_arr[$up5_id]['items'] = [];
            }

            $up5_arr[$up5_id]['num'] += $up4['num'];
            $up5_arr[$up5_id]['value'] = sprintf('%s (%d人)', $arr[1], $up5_arr[$up5_id]['num']);
            array_push($up5_arr[$up5_id]['items'], $up4);
        }

        $csr_arr = [];
        $csr_arr['id'] = '0级^面试人员';
        $csr_arr['value'] = '面试人员';
        $csr_arr['items'] = [];
        $csr_num = 0;

        foreach ($up5_arr as $up5)
        {
            $csr_num += $up5['num'];
            $csr_arr['value'] = sprintf('面试人员 (%d人)', $csr_num);
            array_push($csr_arr['items'], $up5);
        }

        $tree_arr = [];
        array_push($tree_arr, $csr_arr);

        // 调试sql
        $send_sql = str_replace('"','~~',str_replace('\'','~~',$sql));

        // 读出列配置信息
        $columns_arr = [];
        $object_arr = [];

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

        foreach ($results as $row)
        {
            // 前端update_grid信息
            // 主键不能更改
            if ($row->主键 == 1) continue;

            //if ($row->可修改==0 && $row->可新增==0) continue;

            $value_arr = [];
            $value_arr['列名'] = $row->列名;
            $value_arr['字段名'] = $row->字段名;
            $value_arr['列类型'] = $row->列类型;
            $value_arr['赋值类型'] = $row->赋值类型;
            $value_arr['对象'] = $row->对象; 
            $value_arr['是否可修改'] = ($row->可修改=='1' || $row->可修改=='2') ? '是' : '否';
            $value_arr['是否必填'] = ($row->不可为空=='1') ? '是' : '否';
            $value_arr['取值'] = '';

            if ($row->可修改 == 1 || $row->可修改 == 2)
            {
                $value_arr['是否可修改'] = ($row->可修改=='1' || $row->可修改=='2') ? $row->可修改 : '0';
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
            }

            $columns_arr[$row->列名] = $value_arr;

            if (strpos($row->赋值类型,'固定值') !== false && array_key_exists($row->对象,$object_arr) == false)
            {
                $object_arr[$row->对象] = [];
                $object_arr[$row->对象]['对象名称'] = $row->对象;

                $obj_sql = sprintf('
                    select 对象名称,对象值,if(对象显示值="",对象值,对象显示值) as 对象显示值,
                        上级对象名称,上级对象值,if(上级对象显示值="",上级对象值,上级对象显示值) as 上级对象显示值
                    from def_object 
                    where 对象名称="%s"
                        and 有效标识="1"
                        and (属地="" or locate(属地,"%s"))
                    order by convert(对象值 using gbk)',
                    $row->对象, $user_location_authz);

                $qry = $model->select($obj_sql);
                $rslt = $qry->getResult();

                if (count($rslt) == 0)
                {
                    $object_arr[$row->对象]['上级对象名称'] = '';
                    $object_arr[$row->对象][''] = [];
                    $object_arr[$row->对象]['']['对象值'] = [];
                    $object_arr[$row->对象]['']['对象显示值'] = [];

                    array_push($object_arr[$row->对象]['']['对象值'], '');
                    array_push($object_arr[$row->对象]['']['对象显示值'], '');

                    continue;
                }

                foreach($rslt as $vv)
                {
                    $object_arr[$vv->对象名称]['上级对象名称'] = $vv->上级对象名称;
                    if (array_key_exists($vv->上级对象值, $object_arr[$row->对象]) == false)
                    {
                        $object_arr[$row->对象][$vv->上级对象值] = [];
                        $object_arr[$row->对象][$vv->上级对象值]['对象值'] = [];
                        $object_arr[$row->对象][$vv->上级对象值]['对象显示值'] = [];

                        array_push($object_arr[$row->对象][$vv->上级对象值]['对象值'], '');
                        array_push($object_arr[$row->对象][$vv->上级对象值]['对象显示值'], '');
                    }

                    array_push($object_arr[$row->对象][$vv->上级对象值]['对象值'], $vv->对象值);
                    array_push($object_arr[$row->对象][$vv->上级对象值]['对象显示值'], $vv->对象显示值);
                }
            }
        }

        //grid
        $grid_arr = [];

        $send['func_id'] = $menu_id;
        $send['tree_expand_json'] = json_encode($tree_expand);
        $send['tree_json'] = json_encode($tree_arr);
        $send['grid_json'] = json_encode($grid_arr);
        $send['import_func_id'] = '2022';
        $send['import_func_name'] = '面试人员';
        $send['import_func_module'] = 'ee_interview';

        $send['columns_json'] = json_encode($columns_arr);
        $send['object_json'] = json_encode($object_arr);

        $send['SQL'] = json_encode(($user_debug_authz=='1') ? $send_sql : '');

        echo view('Vinterview.php', $send);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 条目信息查询
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function ajax($menu_id='', $type='')
    {
        $request = \Config\Services::request();
        $arg = $request->getJSON(true);

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
            $session_arr['interview_tree_expand'] = $expand_arr;
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
                select 姓名,身份证号,手机号码,属地,
                    招聘渠道,渠道类型,渠道名称,信息来源,实习结束日期,
                    面试业务,面试岗位,一次面试日期 as 面试日期,
                    一次面试结果 as 面试结果,一次面试人 as 面试人,
                    预约培训日期,住宿,备注说明,参培信息
                from ee_interview
                where GUID=%s', $arr[1]);
            $query = $model->select($sql);
            $results = $query->getResult();
        
            array_push($rows_arr, array('表项'=>'属性', '值'=>'查询面试信息'));
            array_push($rows_arr, array('表项'=>'属地', '值'=>$results[0]->属地));
            array_push($rows_arr, array('表项'=>'姓名', '值'=>$results[0]->姓名));
            array_push($rows_arr, array('表项'=>'身份证号', '值'=>$results[0]->身份证号));
            array_push($rows_arr, array('表项'=>'手机号码', '值'=>$results[0]->手机号码));
            array_push($rows_arr, array('表项'=>'属地', '值'=>$results[0]->属地));
            array_push($rows_arr, array('表项'=>'招聘渠道', '值'=>$results[0]->招聘渠道));
            array_push($rows_arr, array('表项'=>'渠道类型', '值'=>$results[0]->渠道类型));
            array_push($rows_arr, array('表项'=>'实习结束日期', '值'=>$results[0]->实习结束日期));
            array_push($rows_arr, array('表项'=>'渠道名称', '值'=>$results[0]->渠道名称));
            array_push($rows_arr, array('表项'=>'信息来源', '值'=>$results[0]->信息来源));
            array_push($rows_arr, array('表项'=>'面试业务', '值'=>$results[0]->面试业务));
            array_push($rows_arr, array('表项'=>'面试岗位', '值'=>$results[0]->面试岗位));
            array_push($rows_arr, array('表项'=>'面试日期', '值'=>$results[0]->面试日期));
            array_push($rows_arr, array('表项'=>'面试人', '值'=>$results[0]->面试人));
            array_push($rows_arr, array('表项'=>'面试结果', '值'=>$results[0]->面试结果));
            array_push($rows_arr, array('表项'=>'预约培训日期', '值'=>$results[0]->预约培训日期));
            array_push($rows_arr, array('表项'=>'住宿', '值'=>$results[0]->住宿));
            array_push($rows_arr, array('表项'=>'备注说明', '值'=>$results[0]->备注说明));
            array_push($rows_arr, array('表项'=>'参培信息', '值'=>$results[0]->参培信息));
        }
        else
        {
            array_push($rows_arr, array('表项'=>'属性', '值'=>'查询面试信息 - 请选择人员'));
        }

        exit(json_encode($rows_arr));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 修改面试人员信息
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function upkeep($menu_id='', $type='')
    {
        $request = \Config\Services::request();
        $arg = $request->getJSON(true);

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_workid = $session->get('user_workid');

        $model = new Mcommon();

        $arg['操作来源'] = '页面修改';
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
            update ee_interview
            set %s where GUID in (%s) ',
            $set_str, $guid_str);

        // 写日志
        $model->sql_log('页面修改', $menu_id, sprintf('表名=ee_interview,GUID="%s"', $guid_str));
        // 更新
        $num = $model->exec($sql);

        exit(sprintf('`修改面试信息`成功,修改 %d 条记录',$num));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 新增面试人员信息
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function insert($menu_id='', $type='')
    {
        $request = \Config\Services::request();
        $arg = $request->getJSON(true);

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_workid = $session->get('user_workid');

        $model = new Mcommon();

        $arg['操作来源'] = '页面新增';
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
            insert into ee_interview (%s) values (%s)',
            $flds_str, $values_str);

        // 新增
        $num = $model->exec($sql);

        exit(sprintf('`新增面试信息`成功,新增 %d 条记录',$num));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 更新培训信息,转培训表
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function tran($menu_id='', $type='')
    {
        $request = \Config\Services::request();
        $arg = $request->getJSON(true);

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

        // 查询ee_train是否已有相同记录
        $sql = sprintf('
            select 姓名,身份证号,max(培训次数) as 培训次数
            from ee_train
            where 有效标识="1"
                and 删除标识!="1"
                and 身份证号 in
                (
                    select 身份证号
                    from ee_interview
                    where GUID in (%s)
                    group by 身份证号
                )
            group by 身份证号', $guid_str);

        $errs = $model->select($sql)->getResultArray();

        if (count($errs) != 0)
        {
            $err_arr = [];
            foreach ($errs as $err)
            {
                if ((int)$arg['培训次数'] != ($err['培训次数']+1))
                {
                    array_push($err_arr, $err['身份证号'].'^培训次数='.$err['培训次数']);
                }
            }
            if (count($err_arr) != 0)
            {
                exit(sprintf('未执行,在培训表中有相关的人员记录,请设置培训次数+1,身份证号{%s}', implode(',', $err_arr)));
            }
        }

        // 更新表ee_interview
        $sql = sprintf('
            update ee_interview
            set 参培信息="%s",
                操作记录="更新,参培信息",操作来源="页面",操作人员="%s",
                结束操作时间="%s",操作时间="%s"
            where GUID in (%s) ',
            $arg['参培信息'], $user_workid,
            date('Y-m-d H:m:s'), date('Y-m-d H:m:s'),
            $guid_str);

        $num = $model->exec($sql);

        // 面试记录导入培训表ee_train
        if ($arg['参培信息'] == '已参培')
        {
            if ($arg['参培信息'] == '已参培') $arg['培训状态'] ='在培';
            $arg['开始操作时间'] = date('Y-m-d H:m:s');
            $arg['结束操作时间'] = '';
    
            // 从session中取出数据
            $session = \Config\Services::session();
            $user_workid = $session->get('user_workid');

            $sql = sprintf('
                insert into ee_train (姓名,身份证号,手机号码,属地,
                    培训业务,培训状态,
                    培训批次,培训老师,
                    培训开始日期,预计完成日期,培训完成日期,
                    培训离开日期,培训离开原因,面试信息,
                    开始操作时间,结束操作时间,
                    操作记录,操作来源,操作人员,
                    有效标识)
                select 姓名,身份证号,手机号码,属地,
                    "%s" as 培训业务,"%s" as 培训状态,
                    "%s" as 培训批次,"%s" as 培训老师,
                    "%s" as 培训开始日期,"%s" as 预计完成日期,"" as 培训完成日期,
                    "" as 培训离开日期,"" as 培训离开原因,"有" as 面试信息,
                    "%s" as 开始操作时间,"" as 结束操作时间,
                    "面试表转入" as 操作记录,"页面" as 操作来源,"%s" as 操作人员,
                    "1" as 有效标识
                from ee_interview
                where GUID in (%s)', 
                $arg['培训业务'],$arg['培训状态'],
                $arg['培训批次'],$arg['培训老师'],
                $arg['培训开始日期'],$arg['预计完成日期'],
                $arg['开始操作时间'],
                $user_workid, $guid_str);
            $num = $model->exec($sql);
        }

        exit(sprintf('`更新参培信息`成功,更新 %d 条记录',$num));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 删除面试信息
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function delete_row($menu_id='', $type='')
    {
        $request = \Config\Services::request();
        $arg = $request->getJSON(true);

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
                $guid_str = sprintf('%s,"%s"', $guid_str ,$guid);
            }
        }

        //原记录更新
        $sql_update = sprintf('
            update ee_interview
            set 结束操作时间="%s",操作时间="%s",
                操作来源="页面删除",操作人员="%s",
                删除标识="1",有效标识="0"
            where GUID in (%s)',
            date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $user_workid, $guid_str);

        // 写日志
        $model->sql_log('页面删除', $menu_id, sprintf('表名=ee_interview,GUID="%s"', $guid_str));
        // 删除
        $num = $model->exec($sql_update);

        exit(sprintf('删除成功,删除 %d 条记录',$num));
    }
}