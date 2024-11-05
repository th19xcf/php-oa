<?php
/* v10.22.1.1.202411052300, from home */
namespace App\Controllers;
use \CodeIgniter\Controller;
use App\Models\Mcommon;

class Frame extends Controller
{
    public function __construct()
    {
        helper(['form', 'url']);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 生成页面
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function index()
    {
        // 从session中取出数据
        $session = \Config\Services::session();
        $arg['user_name'] = $session->get('user_name');
        $arg['user_location'] = $session->get('user_location');

        echo view('Vframe.php', $arg);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 生成页面菜单树
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function get_menu()
    {
        // 从session中取出数据
        $session = \Config\Services::session();
        $user_role_str = $session->get('user_role_str');

        $sql = sprintf(
            'select 
                t1.角色编号,t1.角色名称,
                t1.功能赋权,
                t1.部门编码赋权,t1.部门全称赋权,
                t1.属地赋权,
                t1.新增授权,t1.修改授权,t1.删除授权,t1.整表授权,t1.导入授权,t1.导出授权,
                ifnull(t2.功能编码,"") as 功能编码,
                ifnull(t2.一级菜单,"") as 一级菜单,
                ifnull(t2.二级菜单,"") as 二级菜单,
                ifnull(t2.功能模块,"") as 功能模块,
                ifnull(t2.菜单顺序,"") as 菜单顺序,
                ifnull(t2.菜单显示,"") as 菜单显示,
                ifnull(t3.部门编码字段,"") as 部门编码字段,
                ifnull(t3.部门全称字段,"") as 部门全称字段,
                ifnull(t3.属地字段,"") as 属地字段
            from 
            (
                select 
                    ta.*,
                    ifnull(tb.部门编码赋权,"") as 部门编码赋权,
                    ifnull(tc.部门全称赋权,"") as 部门全称赋权
                from
                (
                    select 角色编号,角色名称,功能赋权,
                        max(新增授权) as 新增授权,
                        max(修改授权) as 修改授权,
                        max(删除授权) as 删除授权,
                        max(整表授权) as 整表授权,
                        max(导入授权) as 导入授权,
                        max(导出授权) as 导出授权,
                        属地赋权
                    from view_role
                    where 有效标识="1" and 角色编号 in (%s)
                    group by 功能赋权
                ) as ta
                left join
                (
                    select 功能赋权,部门编码赋权
                    from view_role
                    where 有效标识="1" and 角色编号 in (%s)
                        and concat(功能赋权,length(部门编码赋权)) in
                        (
                            select concat(功能赋权,min(length(部门编码赋权)))
                            from view_role
                            where 有效标识="1" and 部门编码赋权!=""
                                and 角色编号 in (%s)
                            group by 功能赋权
                        )
                ) as tb on ta.功能赋权=tb.功能赋权
                left join
                (
                    select 功能赋权,部门全称赋权
                    from view_role
                    where 有效标识="1" and 部门全称赋权!=""
                        and 角色编号 in (%s)
                        and concat(功能赋权,length(部门全称赋权)) in
                        (
                            select concat(功能赋权,min(length(部门全称赋权)))
                            from view_role
                            where 有效标识="1" and 部门全称赋权!=""
                                and 角色编号 in (%s)
                            group by 功能赋权
                        )
                ) as tc on ta.功能赋权=tc.功能赋权
            ) as t1
            left join
            (
                select 功能编码,一级菜单,二级菜单,
                    功能模块,功能类型,模块名称,
                    菜单顺序,菜单显示
                from def_function
                where 菜单顺序>0
            ) as t2 on t1.功能赋权=t2.功能编码
            left join
            (
                select 查询模块,部门编码字段,部门全称字段,属地字段
                from def_query_config
            ) as t3 on if(t2.功能类型="查询",t2.模块名称,"")=t3.查询模块
            group by t1.功能赋权
            order by t2.菜单顺序', 
            $user_role_str, $user_role_str,$user_role_str, $user_role_str, $user_role_str);

        $model = new Mcommon();
        $query = $model->select($sql);
        $results = $query->getResult();

        $json = [];

        $function_authz_arr = [];  // 功能访问权限
        foreach ($results as $row)
        {
            // 功能访问权限
            $function_authz_arr[$row->功能赋权] = $row->功能赋权;

            // 部门编码访问权限
            str_replace(' ', '', $row->部门编码赋权);
            str_replace('，', ',', $row->部门编码赋权);
            str_replace(' ', '', $row->部门编码字段);

            $dept_id_cond = '';
            $dept_arr = explode(',', $row->部门编码赋权);
            foreach ($dept_arr as $dept)
            {
                if ($dept == '' || $row->部门编码字段 == '')
                {
                    break;
                }
                if ($dept_id_cond == '')
                {
                    $dept_id_cond = sprintf('left(%s,length("%s"))="%s"', $row->部门编码字段, $dept, $dept);
                }
                else
                {
                    $dept_id_cond = sprintf('%s or left(%s,length("%s"))="%s"', $dept_id_cond, $row->部门编码字段, $dept, $dept);
                }
            }

            // or条件要加括号
            if ($dept_id_cond != '')
            {
                $dept_id_cond = sprintf('(%s)', $dept_id_cond);
            }

            // 部门全称访问权限
            str_replace(' ', '', $row->部门全称赋权);
            str_replace('，', ',', $row->部门全称赋权);
            str_replace(' ', '', $row->部门全称字段);

            $dept_name_cond = '';
            $dept_arr = explode(',', $row->部门全称赋权);
            foreach ($dept_arr as $dept)
            {
                if ($dept == '' || $row->部门全称字段 == '')
                {
                    break;
                }
                if ($dept_name_cond == '')
                {
                    $dept_name_cond = sprintf('instr(%s,"%s")', $row->部门全称字段, $dept);
                }
                else
                {
                    $dept_name_cond = sprintf('%s or instr(%s,"%s")', $dept_name_cond, $row->部门全称字段, $dept);
                }
            }

            // or条件要加括号
            if ($dept_name_cond != '')
            {
                $dept_name_cond = sprintf('(%s)', $dept_name_cond);
            }

            // 部门编码和部门全称条件合并
            $dept_authz_cond = '';
            if ($dept_id_cond != '' && $dept_name_cond != '')
            {
                $dept_authz_cond = sprintf('(%s or %s)', $dept_id_cond, $dept_name_cond);
            }
            else if ($dept_id_cond != '')
            {
                $dept_authz_cond = $dept_id_cond;
            }
            else if ($dept_name_cond != '')
            {
                $dept_authz_cond = $dept_name_cond;
            }

            // 属地访问权限
            str_replace(' ', '', $row->属地赋权);
            str_replace('，', ',', $row->属地赋权);
            str_replace(' ', '', $row->属地字段);

            $location_authz_cond = '';
            $location_authz_arr = explode(',', $row->属地赋权);
            foreach ($location_authz_arr as $location)
            {
                if ($location == '' || $row->属地字段 == '')
                {
                    break;
                }
                if ($location_authz_cond == '')
                {
                    $location_authz_cond = sprintf('instr(%s,"%s")', $row->属地字段, $location);
                }
                else
                {
                    $location_authz_cond = sprintf('%s or instr(%s,"%s")', $location_authz_cond, $row->属地字段, $location);
                }
            }

            // or条件要加括号
            if ($location_authz_cond != '')
            {
                $location_authz_cond = sprintf('(%s)', $location_authz_cond);
            }

            // 存入session
            $session_arr = [];
            $session_arr[$row->功能赋权.'-dept_authz_cond'] = $dept_authz_cond;
            $session_arr[$row->功能赋权.'-location_authz'] = $row->属地赋权;
            $session_arr[$row->功能赋权.'-location_fld'] = $row->属地字段;
            $session_arr[$row->功能赋权.'-location_authz_cond'] = $location_authz_cond;
            $session_arr[$row->功能赋权.'-menu_1'] = $row->一级菜单;
            $session_arr[$row->功能赋权.'-menu_2'] = $row->二级菜单;
            $session_arr[$row->功能赋权.'-add_authz'] = $row->新增授权;
            $session_arr[$row->功能赋权.'-modify_authz'] = $row->修改授权;
            $session_arr[$row->功能赋权.'-delete_authz'] = $row->删除授权;
            $session_arr[$row->功能赋权.'-table_authz'] = $row->整表授权;
            $session_arr[$row->功能赋权.'-import_authz'] = $row->导入授权;
            $session_arr[$row->功能赋权.'-export_authz'] = $row->导出授权;
            $session = \Config\Services::session();
            $session->set($session_arr);

            // 显示标志不等于1,不生成菜单
            if ($row->菜单显示 != 1)
            {
                continue;
            }

            // 生成前端页面菜单数据
            $row->功能模块 = $row->功能模块 . '/' . $row->功能编码 . '?func=' . $row->一级菜单;
            $children = array(
                'text' => sprintf('<a href="javascript:void(0);" tag="%s" onclick="goto(%s)">%s</a>', $row->功能模块, $row->功能编码, $row->二级菜单),
                'expanded' => true
            );

            $json[$row->一级菜单]['text'] = $row->一级菜单;
            $json[$row->一级菜单]['expanded'] = false;
            $json[$row->一级菜单]['children'][] = $children;
        }

        // 功能赋权存入session
        $session_arr = [];
        $session_arr['function_authz'] = $function_authz_arr;
        $session = \Config\Services::session();
        $session->set($session_arr);

        echo json_encode($json, 320);  //256+64,不转义中文+反斜杠
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 通用初始查询模块
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function init($menu_id='', $front_id='', $front_where='')
    {
        $model = new Mcommon();

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_workid = $session->get('user_workid');
        $function_authz = $session->get('function_authz');
        $dept_authz_cond = $session->get($menu_id.'-dept_authz_cond');
        $location_authz_cond = $session->get($menu_id.'-location_authz_cond');
        $user_role = $session->get('user_role');
        $user_debug_authz = $session->get('user_debug_authz');
        $user_upkeep_authz = $session->get('user_upkeep_authz');
        $user_location_str = $session->get('user_location_str');
        $add_authz = $session->get($menu_id.'-add_authz');
        $modify_authz = $session->get($menu_id.'-modify_authz');
        $delete_authz = $session->get($menu_id.'-delete_authz');
        $table_authz = $session->get($menu_id.'-table_authz');
        $import_authz = $session->get($menu_id.'-import_authz');
        $export_authz = $session->get($menu_id.'-export_authz');

        $menu_arr = [];
        $menu_arr['menu_1'] = $session->get($menu_id.'-menu_1');
        $menu_arr['menu_2'] = $session->get($menu_id.'-menu_2');

        //长时间不操作,session失效
        if ($user_workid == '')
        {
            $Arg['NextPage'] = base_url('login/checkin');
            echo view('Vlogin.php', $Arg);
            return;
        }

        $primary_key = '';
        $columns_arr = [];  // 列信息,存入session

        $data_col_arr = [];  // 前端data_grid列信息,用于显示
        $send_columns_arr = []; // 传递到前端的列信息,查询名为公式时,前端报错

        $update_value_arr = [];  // 前端update_grid值信息,用于显示
        $add_value_arr = [];  // 前端add_grid值信息,用于显示
        $cond_value_arr = [];  // 前端cond_grid值信息,用于查询类的显示
        $cond_sp_arr = [];  // 前端cond_grid值信息,用于存储过程类的显示
        $tip_column = '';  // 前端foot显示的字段

        //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
        // 取出查询模块对应的表配置
        //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
        $where = '';
        $group = '';
        $order = '';
        $sp_name = '';  //存储过程模块
        $sp_sql = '';  //存储过程语句
        $query_table = '';
        $query_where = '';
        $query_group = '';
        $query_order = '';
        $drill_module = '';  //钻取模块
        $drill_func_id = '';  //钻取功能编码
        $import_module = '';  //导入模块
        $before_insert = '';  //新增前处理模块
        $after_insert = '';  //新增后处理模块
        $before_update = '';  //更新后处理模块
        $after_update = '';  //更新后处理模块
        $data_upkeep = '';  //数据整理模块
        $chart_func_id = '';  //图形模块
        $result_count = '';
        $data_table = '';
        $data_model = '';

        $drill_arr = [];  // 钻取模块参数

        $sql = sprintf('
            select 
                功能编码,
                模块名称,模块类型,
                查询表名,
                数据表名,数据模式,
                查询条件,汇总条件,排序条件,初始条数,
                新增前处理模块,新增后处理模块,
                更新前处理模块,更新后处理模块,
                数据整理模块,
                钻取模块,钻取功能编码,
                导入模块,导入标签名称,
                图形模块
            from view_function
            where 功能编码="%s"
            group by 功能编码', $menu_id);

        $query = $model->select($sql);
        $results = $query->getResult();
        foreach ($results as $row)
        {
            if ($row->模块类型 == '存储过程')
            {
                $sp_name = $row->模块名称;
            }

            $query_table = $row->查询表名;
            $result_count = $row->初始条数;

            $query_where = $row->查询条件;
            if(strpos($row->查询条件, '$角色') !== false)
            {
                $query_where = str_replace('$角色', $user_role, $row->查询条件);
            }

            $query_group = $row->汇总条件;
            $query_order = $row->排序条件;

            $drill_module = $row->钻取模块;
            $drill_func_id = $row->钻取功能编码;

            $import_module = $row->导入模块;
            $import_tag_name = $row->导入标签名称;

            $before_insert = $row->新增前处理模块;
            $after_insert = $row->新增后处理模块;
            $before_update = $row->更新后处理模块;
            $after_update = $row->更新后处理模块;
            $data_upkeep = $row->数据整理模块;

            $chart_func_id = $row->图形模块;

            $data_table = $row->数据表名;
            $data_model = $row->数据模式;
            break;
        }

        $tb_arr = [];  // 控制菜单栏

        $tb_arr['钻取授权'] = ($drill_module!='' && array_key_exists($drill_func_id,$function_authz) == true) ? true : false;
        $tb_arr['导入授权'] = ($import_module!='') ? true : false;
        $tb_arr['数据整理'] = ($user_upkeep_authz=='1' && $data_upkeep!='') ? true : false;
        $tb_arr['SQL'] = ($user_debug_authz=='1') ? true : false;

        // 读出存储过程参数
        $sp_param_str = '';

        if ($sp_name != '')
        {
            $sp_sql = sprintf('
                select 存储过程模块,参数名,参数类型,不可为空,缺省值,顺序
                from def_sp_param
                where 存储过程模块="%s"
                order by 顺序',
                $sp_name);

            $sp_results = $model->select($sp_sql)->getResult();
            foreach ($sp_results as $sp_row)
            {
                if ($sp_param_str != '')
                {
                    $sp_param_str = $sp_param_str . ',';
                }
                switch ($sp_row->参数类型)
                {
                    case '数值':
                        $sp_param_str = sprintf('%s%s', $sp_param_str, $sp_row->缺省值);
                        break;
                    case '字符':
                    case '日期':
                        $sp_param_str = sprintf('%s"%s"', $sp_param_str, $sp_row->缺省值);
                        break;
                }

                // 前端要显示的cond_grid条件信息
                $cond = [];
                $cond['列名'] = $sp_row->参数名;
                $cond['字段名'] = $sp_row->参数名;
                $cond['列类型'] = $sp_row->参数类型;
                $cond['是否必填'] = ($sp_row->不可为空=='1') ? '是' : '否';

                array_push($cond_sp_arr, $cond);
            }

            $sp_sql = sprintf('call %s(%s)', $query_table, $sp_param_str);
        }

        // 读出钻取模块参数
        $sql = sprintf('
            select 钻取模块,页面选项,t1.功能编码,钻取字段,钻取条件,
                if(t2.二级菜单 is null,"",if(t1.标签副名称="",t2.二级菜单,concat(t2.二级菜单,"-",t1.标签副名称))) as 标签名称
            from def_drill_config as t1
            left join def_function as t2 on t1.功能编码=t2.功能编码
            where 钻取模块="%s"
            order by 顺序,convert(页面选项 using gbk)', $drill_module);

        $results = $model->select($sql)->getResult();
        foreach ($results as $row)
        {
            str_replace(' ', '', $row->钻取字段);
            str_replace('；', ';', $row->钻取字段);

            $arr = [];
            $arr['模块名称'] = $row->钻取模块;
            $arr['页面选项'] = $row->页面选项;
            $arr['功能编码'] = $row->功能编码;
            $arr['钻取字段'] = $row->钻取字段;
            $arr['钻取条件'] = $row->钻取条件;
            $arr['标签名称'] = $row->标签名称;
            array_push($drill_arr, $arr);
        }

        //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+= 
        // 处理钻取功能相关数据
        // 钻取条件格式:源表字段^目标表字段 [^类型^操作符]
        //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+= 
        $cond_arr = json_decode($front_where);
        $front_where = '';
        $disp_col_arr = [];  // 显示列
        $color_arr = [];  // 颜色标注
        $color_col_num = 0;

        if ($front_id != '')
        {
            $col_arr = explode(';', $cond_arr->钻取字段);
            $front_where = str_replace('`', '"', $cond_arr->钻取条件);
            $disp_col_arr = $cond_arr->字段选择;

            foreach ($cond_arr as $key => $value)
            {
                if ($key == '字段选择')
                {
                    continue;
                }
                if ($key == '颜色标注')
                {
                    $color_arr = $value;
                    continue;
                }

                if ($key == '钻取字段' || $key == '钻取条件') continue;
                $front_where = str_replace(sprintf('$%s',$key), $value, $front_where);
            }
        }

        //+=+=+=+=+=+=+=+=+=+=+=+= 
        // 处理列的相关数据
        //+=+=+=+=+=+=+=+=+=+=+=+= 
        // 前端data_grid列信息,手工增加选取列和序号列
        $data_col_arr['选取']['field'] = '选取';
        $data_col_arr['选取']['width'] = 100;
        $data_col_arr['选取']['resizable'] = true;
        $data_col_arr['选取']['headerCheckboxSelection'] = true;
        $data_col_arr['选取']['checkboxSelection'] = true;

        $data_col_arr['序号']['field'] = '序号';
        $data_col_arr['序号']['type'] = 'numericColumn';
        $data_col_arr['序号']['filter'] = 'agNumberColumnFilter';
        $data_col_arr['序号']['width'] = 90;
        $data_col_arr['序号']['resizable'] = true;
        $data_col_arr['序号']['sortable'] = true;

        $object_arr = [];  // 下拉选择的对象值
        $cond_obj_arr = [];  // 条件下拉选择的对象值
        $update_obj_arr = [];  // 修改下拉选择的对象值

        // 读出列配置信息
        $sql = sprintf('
            select 功能编码,字段模块,部门编码字段,部门全称字段,属地字段,
                列名,列类型,列宽度,字段名,查询名,
                赋值类型,对象,对象名称,对象表名,主键,
                可筛选,可汇总,可新增,可修改,不可为空,可颜色标注,
                提示条件,提示样式设置,异常条件,异常样式设置,字符转换,
                列顺序
            from view_function
            where 功能编码="%s" and 列顺序>0
            group by 列名
            order by 列顺序', $menu_id);

        $query = $model->select($sql);
        $results = $query->getResult();

        foreach ($results as $row)
        {
            if ($row->主键 == 1)
            {
                $primary_key = $row->列名;
            }

            // 列信息
            $arr = [];

            $arr['列名'] = $row->列名;
            $arr['类型'] = $row->列类型;
            $arr['字段名'] = $row->字段名;
            $arr['查询名'] = $row->查询名;
            $arr['主键'] = $row->主键;
            $arr['赋值类型'] = $row->赋值类型;
            $arr['对象'] = $row->对象;
            $arr['对象名称'] = $row->对象名称;
            $arr['对象表名'] = $row->对象表名;
            $arr['可汇总'] = $row->可汇总;
            $arr['可修改'] = $row->可修改;
            $arr['可新增'] = $row->可新增;
            $arr['不可为空'] = $row->不可为空;
            $arr['可颜色标注'] = $row->可颜色标注;
            $arr['提示条件'] = $row->提示条件;
            $arr['提示样式'] = $row->提示样式设置;
            $arr['异常条件'] = $row->异常条件;
            $arr['异常样式'] = $row->异常样式设置;
            $arr['字符转换'] = $row->字符转换;

            array_push($columns_arr, $arr);

            $arr['查询名'] = '';
            array_push($send_columns_arr, $arr);

            if ($row->提示条件 == 1)
            {
                $tip_column = $row->列名;
            }

            foreach ($color_arr as $color_col)
            {
                if ($color_col == $row->列名)
                {
                    $color_col_num ++;
                    break;
                }
            }

            // 前端data_grid列信息,用于显示
            $data_col_arr[$row->列名]['field'] = $row->列名;
            $data_col_arr[$row->列名]['sortable'] = true;
            $data_col_arr[$row->列名]['filter'] = true;
            $data_col_arr[$row->列名]['resizable'] = true;
            if ($row->列宽度 == 0)
            {
                $data_col_arr[$row->列名]['width'] = strlen($row->列名)*4 + 60;
            }
            else
            {
                $data_col_arr[$row->列名]['width'] = $row->列宽度;
            }
            if ($row->主键 == 1)
            {
                $data_col_arr[$row->列名]['hide'] = true;
            }
            if ($row->列类型 == '数值')
            {
                $data_col_arr[$row->列名]['type'] = 'numericColumn';
                $data_col_arr[$row->列名]['filter'] = 'agNumberColumnFilter';
            }

            // 前端要显示的cond_grid条件信息
            $cond = [];
            $cond['列名'] = $row->列名;
            $cond['字段名'] = $row->字段名;
            $cond['列类型'] = $row->列类型;
            $cond['汇总'] = '';
            $cond['平均'] = '';
            $cond['条件1'] = '';
            $cond['参数1'] = '';
            $cond['条件关系'] = '';
            $cond['条件2'] = '';
            $cond['参数2'] = '';
            $cond['计算方式'] = '';

            array_push($cond_value_arr, $cond);

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
                    $row->对象, $user_location_str);

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

            if ($row->可修改 == 1 || $row->可修改 == 2)
            {
                array_push($update_value_arr, $value_arr);
            }
            if ($row->可新增 == 1)
            {
                array_push($add_value_arr, $value_arr);
            }
        }

        // 拼出查询语句
        $select_str = '';
        $send_str = '';
        foreach ($columns_arr as $column) 
        {
            if ($select_str != '')
            {
                $select_str = $select_str . ',';
                $send_str = $send_str . ',';
            }

            if ($column['字符转换'] == '1')
            {
                $select_str = sprintf('%s replace(replace(%s,"\"","~~"),"\'","~~") as `%s`', $select_str, $column['查询名'], $column['列名']);
            }
            else
            {
                $select_str = sprintf('%s %s as `%s`', $select_str, $column['查询名'], $column['列名']);
            }

            $send_str = sprintf('%s %s as `%s`', $send_str, $column['查询名'], $column['列名']);
        }

        $query_sql = sprintf('select "" as 选取,(@i:=@i+1) as 序号,%s 
            from %s,(select @i:=0) as xh', 
            $select_str, $query_table);

        $send_sql = sprintf('select "" as 选取,(@i:=@i+1) as 序号,%s 
            from %s,(select @i:=0) as xh', 
            $send_str, $query_table);

        // 加上初始查询条件
        if ($query_where != '')
        {
            $where = $query_where;
        }

        // 条件语句加上部门授权条件
        if ($dept_authz_cond != '')
        {
            $where = ($where == '') ? $dept_authz_cond : $where . ' and ' . ($dept_authz_cond);
        }

        // 条件语句加上属地条件
        if ($location_authz_cond != '')
        {
            $where = ($where == '') ? $location_authz_cond : $where . ' and ' . $location_authz_cond;
        }

        // 数据钻取,条件语句加上前端选定的条件
        if ($front_where != '')
        {
            $where = ($where == '') ? $front_where : sprintf('%s and %s',$where, $front_where);
        }

        if ($where != '')
        {
            $query_sql = sprintf('%s where %s', $query_sql, $where);
            $send_sql = sprintf('%s where %s', $send_sql, $where);
        }

        // 加上group by 条件
        if ($query_group != '')
        {
            $group = $query_group;
            $query_sql = sprintf('%s group by %s', $query_sql, $group);
            $send_sql = sprintf('%s group by %s', $send_sql, $group);
        }

        // 加上order by
        if ($query_order != '')
        {
            $order = $query_order;
            $query_sql = sprintf('%s order by %s', $query_sql, $order);
            $send_sql = sprintf('%s order by %s', $send_sql, $order);
        }

        // 加上初始结果条数
        if ($result_count > 0)
        {
            $query_sql = sprintf('%s limit %d', $query_sql, $result_count);
            $send_sql = sprintf('%s limit %d', $send_sql, $result_count);
        }

        $send_results = [];
        if ($sp_name != '')
        {
            // 写日志
            $model->sql_log('存储过程', $menu_id, sprintf('sp=%s,条件=%s', $sp_name, str_replace('"','`',$sp_param_str)));

            $send_results = $model->select($sp_sql)->getResult();
            $send_sql = $sp_sql;
        }
        else
        {
            // 写日志
            $model->sql_log('查询', $menu_id, sprintf('表名=%s,条件=%s', $query_table, str_replace('"','`',$where)));
            $send_results = $model->select($query_sql)->getResult();
        }

        $send_sql = str_replace('\'','~~',$send_sql);
        $send_sql = str_replace('"','~~',$send_sql);

        // chart session初始化
        $session_arr = [];
        $session_arr[$menu_id.'-chart_drill_arr'] = [];
        $session_arr[$menu_id.'-chart_drill_cond_str'] = '';
        $session_arr[$menu_id.'-chart_drill_title_str'] = '';

        $session = \Config\Services::session();
        $session->set($session_arr);

        // 生成前端图形数据
        $chart_arr = $this->get_chart_data($menu_id, $chart_func_id, '', []);

        // 存入session
        $session_arr = [];
        $session_arr[$menu_id.'-select_str'] = $select_str;
        $session_arr[$menu_id.'-query_str'] = $query_sql;
        $session_arr[$menu_id.'-query_table'] = $query_table;
        $session_arr[$menu_id.'-columns_arr'] = $columns_arr;
        $session_arr[$menu_id.'-primary_key'] = $primary_key;
        $session_arr[$menu_id.'-back_where'] = $where;
        $session_arr[$menu_id.'-back_group'] = $group;
        $session_arr[$menu_id.'-back_order'] = $order;
        $session_arr[$menu_id.'-sp_name'] = $sp_name;
        $session_arr[$menu_id.'-sp_str'] = $sp_sql;

        $session_arr[$menu_id.'-import_module'] = $import_module;
        $session_arr[$menu_id.'-import_tag_name'] = $import_tag_name;

        $session_arr[$menu_id.'-before_insert'] = $before_insert;
        $session_arr[$menu_id.'-after_insert'] = $after_insert;
        $session_arr[$menu_id.'-after_update'] = $after_update;
        $session_arr[$menu_id.'-before_update'] = $before_update;
        $session_arr[$menu_id.'-data_upkeep'] = $data_upkeep;

        $session_arr[$menu_id.'-data_table'] = $data_table;
        $session_arr[$menu_id.'-data_model'] = $data_model;

        $session = \Config\Services::session();
        $session->set($session_arr);

        $tb_arr['新增授权'] = ($add_authz=='1') ? true : false ;
        $tb_arr['修改授权'] = ($modify_authz=='1') ? true : false ;
        $tb_arr['删除授权'] = ($delete_authz=='1') ? true : false ;
        $tb_arr['整表授权'] = ($table_authz=='1') ? true : false ;
        $tb_arr['导入授权'] = ($import_authz=='1' && $import_module!='') ? true : false ;
        $tb_arr['导出授权'] = ($export_authz=='1') ? true : false ;

        //返回页面
        $send = [];
        $send['SQL'] = json_encode(($user_debug_authz=='1') ? $send_sql : '');
        $send['menu_json'] = json_encode($menu_arr);
        $send['toolbar_json'] = json_encode($tb_arr);
        $send['columns_json'] = json_encode($send_columns_arr);
        $send['data_col_json'] = json_encode($data_col_arr);
        $send['data_value_json'] = json_encode($send_results);
        $send['update_value_json'] = json_encode($update_value_arr);
        $send['add_value_json'] = json_encode($add_value_arr);

        if ($sp_name != '')
        {
            $send['cond_value_json'] = json_encode($cond_sp_arr);
            $send['cond_model'] = '存储过程';
        }
        else
        {
            $send['cond_value_json'] = json_encode($cond_value_arr);
            $send['cond_model'] = '数据查询';
        }

        $send['object_json'] = json_encode($object_arr);
        $send['cond_obj_json'] = json_encode($cond_obj_arr);
        $send['update_obj_json'] = json_encode($update_obj_arr);
        $send['func_id'] = $menu_id;
        $send['data_model'] = $data_model;
        $send['primary_key'] = $primary_key;
        $send['back_where'] = strtr($where, '"', '');
        $send['back_group'] = $group;

        $send['drill_json'] = json_encode($drill_arr);
        $send['disp_col_json'] = json_encode($disp_col_arr);

        if ($color_col_num != 2)
        {
            $color_arr = [];
        }
        $send['color_json'] = json_encode($color_arr);

        $send['import_module'] = $import_module;
        $send['import_tag_name'] = $import_tag_name;
        $send['tip_column'] = $tip_column;
        $popup_arr = $this->get_popup($menu_id);

        //弹窗条件
        $popup_arr = $this->get_popup($menu_id);

        $send['popup_grid_json'] = json_encode($popup_arr[0]);
        $send['popup_obj_json'] = json_encode($popup_arr[1]);

        //图形数据
        $send['chart_data_json'] = json_encode($chart_arr);

        echo view('Vgrid_aggrid.php', $send);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 前端设置语句查询条件,数据查询
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function set_query_condition($menu_id='')
    {
        $model = new Mcommon();

        $request = \Config\Services::request();
        $cond_arr = $request->getJSON(true);

        $where = '';
        $group = '';
        $order = '';

        $front_where = '';
        $front_group = '';

        $sum_str = '';
        $avg_str = '';
        $max_str = '';
        $min_str = '';

        foreach ($cond_arr as $cond)
        {
            $cond_str = '';
            $opt_1 = '';
            $opt_2 = '';
            $cond_1 = '';
            $cond_2 = '';

            if ($cond['cond_1'] != '')
            {
                switch ($cond['cond_1'])
                {
                    case '大于':
                        $opt_1 = '>';
                        break;
                    case '等于':
                    case '等于空':
                        $opt_1 = '=';
                        break;
                    case '小于':
                        $opt_1 = '<';
                        break;
                    case '大于等于':
                        $opt_1 = '>=';
                        break;
                    case '小于等于':
                        $opt_1 = '<=';
                        break;
                    case '不等于':
                    case '不等于空':
                        $opt_1 = '!=';
                        break;
                    case '包含':
                        $opt_1 = 'in';
                        break;
                    case '不包含':
                        $opt_1 = 'not in';
                        break;
                }

                if ($cond['cond_1'] == '数值')
                {
                    if ($opt_1 != 'in' && $opt_1 != 'not in')
                    {
                        $cond_1 = sprintf(' %s%s%s ', $cond['fld_name'], $opt_1, $cond['arg_1']);
                    }
                    else
                    {
                        $cond_1 = sprintf(' %s %s (%s) ', $cond['fld_name'], $opt_1, $cond['arg_1']);
                    }
                }
                else
                {
                    if ($opt_1 == 'in')
                    {
                        $cond_1 = sprintf(' instr(%s,"%s") ', $cond['fld_name'], $cond['arg_1']);
                    }
                    else if ($opt_1 == 'not in')
                    {
                        $cond_1 = sprintf(' instr(%s,"%s")=0 ', $cond['fld_name'], $cond['arg_1']);
                    }
                    else
                    {
                        $cond_1 = sprintf(' %s%s"%s" ', $cond['fld_name'], $opt_1, $cond['arg_1']);
                    }
                }
            }

            if ($cond['cond_2'] != '')
            {
                switch ($cond['cond_2'])
                {
                    case '大于':
                        $opt_2 = '>';
                        break;
                    case '等于':
                    case '等于空':
                        $opt_2 = '=';
                        break;
                    case '小于':
                        $opt_2 = '<';
                        break;
                    case '大于等于':
                        $opt_2 = '>=';
                        break;
                    case '小于等于':
                        $opt_2 = '<=';
                        break;
                    case '不等于':
                    case '不等于空':
                        $opt_2 = '!=';
                        break;
                    case '包含':
                        $opt_2 = ' in ';
                        break;
                    case '不包含':
                        $opt_2 = ' not in ';
                        break;
                }

                if ($cond['cond_2'] == '数值')
                {
                    if ($opt_2!='in' && $opt_2!='not in')
                    {
                        $cond_2 = sprintf(' %s%s%s ', $cond['fld_name'], $opt_2, $cond['arg_2']);
                    }
                    else
                    {
                        $cond_2 = sprintf(' %s %s (%s) ', $cond['fld_name'], $opt_2, $cond['arg_2']);
                    }
                }
                else
                {
                    if ($opt_2 == 'in')
                    {
                        $cond_2 = sprintf(' instr(%s,"%s") ', $cond['fld_name'], $cond['arg_2']);
                    }
                    else if ($opt_2 == 'not in')
                    {
                        $cond_2 = sprintf(' instr(%s,"%s")=0 ', $cond['fld_name'], $cond['arg_2']);
                    }
                    else
                    {
                        $cond_2 = sprintf(' %s%s"%s" ', $cond['fld_name'], $opt_2, $cond['arg_2']);
                    }
                }
            }

            switch ($cond['and_or'])
            {
                case '并且':
                    $cond_str = sprintf('%s and %s', $cond_1, $cond_2);
                    break;
                case '或者':
                    $cond_str = sprintf('%s or %s', $cond_1, $cond_2);
                    break;
                default:
                    $cond_str = $cond_1;
            }

            if ($cond_str != '')
            {
                if ($front_where != '') $front_where = $front_where . ' and ';
                $front_where = $front_where . $cond_str;    
            }

            if ($cond['group'] == true)
            {
                if ($front_group != '') $front_group = $front_group . ' , ';
                $front_group = $front_group . $cond['fld_name'];
            }

            if ($cond['sum_avg'] == '合计')
            {
                if ($sum_str != '') $sum_str = $sum_str . ' , ';
                $sum_str = $sum_str . $cond['fld_name'];
            }

            else if ($cond['sum_avg'] == '平均')
            {
                if ($avg_str != '') $avg_str = $avg_str . ' , ';
                $avg_str = $avg_str . $cond['fld_name'];
            }

            else if ($cond['sum_avg'] == '最大')
            {
                if ($max_str != '') $max_str = $max_str . ' , ';
                $max_str = $max_str . $cond['fld_name'];
            }

            else if ($cond['sum_avg'] == '平均')
            {
                if ($min_str != '') $min_str = $min_str . ' , ';
                $min_str = $min_str . $cond['fld_name'];
            }
        }

        // 从session中取出数据
        $session = \Config\Services::session();
        $query_table = $session->get($menu_id.'-query_table');
        $columns_arr = $session->get($menu_id.'-columns_arr');
        $select_str = $session->get($menu_id.'-select_str');
        $where = $session->get($menu_id.'-back_where');
        $group = $session->get($menu_id.'-back_group');
        $order = $session->get($menu_id.'-back_order');

        // 拼出查询语句
        $select_str = '';
        foreach ($columns_arr as $column) 
        {
            if ($select_str != '')
            {
                $select_str = $select_str . ',';
            }

            $sum_avg_str = '';
            foreach ($cond_arr as $cond)
            {
                if ($cond['sum_avg'] == '')
                {
                    continue;
                }
                if ($column['列名']==$cond['col_name'] && $cond['sum_avg']=='合计')
                {
                    $sum_avg_str = sprintf('sum(%s)', $column['查询名']);
                    break;
                }
                if ($column['列名']==$cond['col_name'] && $cond['sum_avg']=='平均')
                {
                    $sum_avg_str = sprintf('round(avg(%s),2)', $column['查询名']);
                    break;
                }
                if ($column['列名']==$cond['col_name'] && $cond['sum_avg']=='最大')
                {
                    $sum_avg_str = sprintf('max(%s)', $column['查询名']);
                    break;
                }
                if ($column['列名']==$cond['col_name'] && $cond['sum_avg']=='最小')
                {
                    $sum_avg_str = sprintf('min(%s)', $column['查询名']);
                    break;
                }
                if ($column['列名']==$cond['col_name'] && $cond['sum_avg']=='计数')
                {
                    $sum_avg_str = sprintf('count(distinct(%s))', $column['查询名']);
                    break;
                }
            }

            if ($sum_avg_str != '')
            {
                $select_str = sprintf('%s%s as `%s`',$select_str, $sum_avg_str, $column['列名']);
            }
            else
            {
                $select_str = sprintf('%s%s as `%s`', $select_str, $column['查询名'], $column['列名']);
            }
        }

        $sql = sprintf('select "" as 选取,(@i:=@i+1) as 序号,%s from %s,(select @i:=0) as xh', 
            $select_str, $query_table);

        // 拼出查询条件
        if ($front_where != '')
        {
            if ($where != '') $where = $where . ' and ';
            $where = $where . $front_where;
        }

        if ($where != '')
        {
            $sql = $sql . ' where ' . $where;
        }

        // 拼出汇总条件
        if ($front_group != '')
        {
            if ($group != '') $group = $group . ',';
            $group = $group . $front_group;
        }

        if ($group != '')
        {
            $sql = $sql . ' group by ' . $group;
        }

        if ($order != '')
        {
            $sql = $sql . ' order by ' . $order;
        }

        // 写日志
        $model->sql_log('条件查询',$menu_id,sprintf('表=%s,条件=%s', $query_table, str_replace('"','',$where)));

        // 读出数据
        $query = $model->select($sql);
        $results = $query->getResult();

        exit(json_encode($results));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 前端设置存储过程查询条件,数据查询
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function set_sp_condition($menu_id='')
    {
        $request = \Config\Services::request();
        $cond_arr = $request->getJSON(true);

        // 从session中取出数据
        $session = \Config\Services::session();
        $sp_name = $session->get($menu_id.'-sp_name');
        $query_table = $session->get($menu_id.'-query_table');

        // 拼出查询语句
        $sp_param_str = '';

        foreach ($cond_arr as $param)
        {
            if ($sp_param_str != '')
            {
                $sp_param_str = $sp_param_str . ',';
            }
            switch ($param['type'])
            {
                case '数值':
                    $sp_param_str = sprintf('%s%s', $sp_param_str, $param['value']);
                    break;
                case '字符':
                case '日期':
                    $sp_param_str = sprintf('%s"%s"', $sp_param_str, $param['value']);
                    break;
            }
        }

        $sp_sql = sprintf('call %s(%s)', $query_table, $sp_param_str);

        $model = new Mcommon();

        // 写日志
        $model->sql_log('存储过程', $menu_id, sprintf('sp=%s,条件=%s', $sp_name, str_replace('"','`',$sp_param_str)));

        // 读出数据
        $results = $model->select($sp_sql)->getResult();

        // 存入session
        $session_arr = [];
        $session_arr[$menu_id.'-sp_str'] = $sp_sql;

        $session = \Config\Services::session();
        $session->set($session_arr);

        exit(json_encode($results));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 更新记录
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function update_row($menu_id='')
    {
        $request = \Config\Services::request();
        $row_arr = $request->getJSON(true);

        // 从session中取出数据
        $session = \Config\Services::session();
        $data_model = $session->get($menu_id.'-data_model');
        $before_update = $session->get($menu_id.'-before_update');
        $after_update = $session->get($menu_id.'-after_update');
        $primary_key = $session->get($menu_id.'-primary_key');

        $this->set_popup($menu_id, $row_arr);

        $model = new Mcommon();

        // 执行前处理
        if ($before_update != '')
        {
            foreach ($row_arr as $row)
            {
                if (strpos($before_update,$row['fld_name']) === false) continue;
                $before_update = str_replace(sprintf('$%s',$row['fld_name']), $row['value'], $before_update);
            }

            $arr = $model->select(sprintf('call %s', $before_update))->getResultArray();
            foreach ($arr as $item)  // 非空,退出
            {
                foreach ($item as $key => $value)
                {
                    if ($value != '') exit($value);
                }
                break;
            }
        }

        $num = 0;
        switch ($data_model)
        {
            case '0': //无额外字段
                $num = $this->update_row_0($menu_id, $row_arr);
                break;
            case '1': //有额外字段,模式1
                $num = $this->update_row_1($menu_id, $row_arr);
                break;
            case '2': //有额外字段,模式2
                $num = $this->update_row_2($menu_id, $row_arr);
                break;
            default:
                exit(sprintf('更新失败,数据模式[-%d-]错误',$data_model));
        }

        // 执行后处理
        if ($after_update != '')
        {
            $key_str = $this->get_where($row_arr, $primary_key, 'str');
            $model->select(sprintf('call %s("更新","%s")', $after_update, $key_str));
        }

        exit(sprintf('更新[%d]成功,更新 %d 条记录',$data_model,$num));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 更新记录,模式0,无额外字段
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function update_row_0($menu_id, $arg)
    {
        $row_arr = $arg;

        // 从session中取出数据
        $session = \Config\Services::session();
        $data_table = $session->get($menu_id.'-data_table');
        $primary_key = $session->get($menu_id.'-primary_key');

        $change_fld_str = ''; //变更表项

        $set = '';
        $where = $this->get_where($row_arr, $primary_key);

        foreach ($row_arr as $row)
        {
            // 未修改的字段不处理
            if ($row['modified'] == false) continue;

            $set_str = '';
            switch ($row['type'])
            {
                case '数值':
                    $set_str = sprintf('%s=%s', $row['fld_name'], $row['value']);
                    break;
                case '字符':
                case '日期':
                    $set_str = sprintf('%s=\'%s\'', $row['fld_name'], $row['value']);
                    break;
                default:
                    $set_str = sprintf('%s=\'%s\'', $row['fld_name'], $row['value']);
                    break;
            }

            if ($set != '')
            {
                $set = $set . ',';
                $change_fld_str = $change_fld_str . ',';
            }
            $set = $set . $set_str;
            $change_fld_str = $change_fld_str . $row['fld_name'];
        }

        $sql = sprintf('update %s set %s where %s', $data_table, $set, $where);

        $model = new Mcommon();

        // 写日志
        $model->sql_log('更新[0]', $menu_id, sprintf('表名=`%s`,更新=`%s`,GUID=`%s`', $data_table, $change_fld_str, $primary_key));

        $num = $model->exec($sql);
        return $num;
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 更新记录,模式1,有额外字段 (原记录不变)
    // 额外字段:操作记录,操作来源,操作人员,操作时间,校验标识,删除标识,有效标识
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function update_row_1($menu_id, $arg)
    {
        $row_arr = $arg;

        // 从session中取出数据
        $session = \Config\Services::session();
        $data_table = $session->get($menu_id.'-data_table');
        $primary_key = $session->get($menu_id.'-primary_key');
        $user_workid = $session->get('user_workid');

        $model = new Mcommon();

        $where = $this->get_where($row_arr, $primary_key);

        // 更新原记录
        $change_fld_str = ''; //变更的表项
        $set = ''; //更新字段

        foreach ($row_arr as $row)
        {
            // 未修改的字段不处理
            if ($row['modified'] == false) continue;

            $set_str = '';
            switch ($row['type'])
            {
                case '数值':
                    $set_str = sprintf('%s=%s', $row['fld_name'], $row['value']);
                    break;
                case '字符':
                case '日期':
                    $set_str = sprintf('%s=\'%s\'', $row['fld_name'], $row['value']);
                    break;
                default:
                    $set_str = sprintf('%s=\'%s\'', $row['fld_name'], $row['value']);
                    break;
            }

            if ($set != '')
            {
                $set = $set . ',';
                $change_fld_str = $change_fld_str . ',';
            }
            $set = $set . $set_str;
            $change_fld_str = $change_fld_str . $row['fld_name'];
        }

        // 更新操作记录、操作人员、有效标识等固定字段
        $set = sprintf('
            %s,操作记录="更新[1],%s",操作来源="页面",操作人员="%s",
            结束操作时间="%s",操作时间="%s",
            有效标识="0"', 
            $set, $change_fld_str, $user_workid,
            date('Y-m-d H:i:s'), date('Y-m-d H:i:s') );

        // 插入新记录
        $read_str = '';
        $insert_str = '';

        $fields = $model->get_fields($data_table);
        foreach ($fields as $field)
        {
            if ($field == $primary_key || $field == '操作时间') continue;

            $fld_str = $field;
                
            // 字段处理
            switch ($field)
            {
                case '操作记录':
                    $fld_str = '"" as 操作记录';
                    break;
                case '操作来源':
                    $fld_str = '"页面" as 操作来源';
                    break;
                case '操作人员':
                    $fld_str = sprintf('\'%s\' as 操作人员', $user_workid);
                    break;
                case '开始操作时间':
                    $fld_str = sprintf('\'%s\' as 开始操作时间', date('Y-m-d H:i:s'));
                    break;
                case '结束操作时间':
                    $fld_str = '"" as 结束操作时间';
                    break;
                case '校验标识':
                    $fld_str = '"0" as 校验标识';
                    break;
                case '删除标识':
                    $fld_str = '"0" as 删除标识';
                    break;
                case '有效标识':
                    $fld_str = '"1" as 有效标识';
                    break;
                default:
                    // 业务字段
                    foreach ($row_arr as $row)
                    {
                        if ($row['fld_name'] != $field) continue;
                        // 未修改的字段不处理
                        if ($row['modified'] == false) break;
        
                        // 更新字段处理
                        switch ($row['type'])
                        {
                            case '数值':
                                $fld_str = sprintf('%s as %s', $row['value'], $row['fld_name']);
                                break;
                            case '字符':
                            case '日期':
                                $fld_str = sprintf('\'%s\' as %s', $row['value'], $row['fld_name']);
                                break;
                            default:
                                $fld_str = sprintf('\'%s\' as %s', $row['value'], $row['fld_name']);
                                break;
                        }
                    }
                    break;
            }

            if ($read_str != '')
            {
                $read_str = $read_str . ',';
            }
            $read_str = $read_str . $fld_str;

            if ($insert_str != '')
            {
                $insert_str = $insert_str . ',';
            }
            $insert_str = $insert_str . $field;
        }

        $sql_select = sprintf('select %s from %s where %s', $read_str, $data_table, $where);

        // 插入新记录
        $sql_insert = sprintf('
            insert into %s (%s) %s', 
            $data_table, $insert_str, $sql_select);

        // 更新旧记录
        $sql_update = sprintf('update %s set %s where %s', $data_table, $set, $where);

        $model = new Mcommon();

        // 写日志
        $model->sql_log('更新[1]', $menu_id, sprintf('表名=`%s`,更新=`%s`,GUID=`%s`', $data_table, $change_fld_str, $primary_key));

        $num = $model->exec($sql_insert);
        $num = $model->exec($sql_update);
        return $num;
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 更新记录,模式2,有额外字段 (原记录更新,流水账模式)
    // 额外字段:操作记录,操作来源,操作人员,操作时间,校验标识,删除标识,有效标识,记录开始日期,记录结束日期
    // 比模式1多出:记录开始日期,记录结束日期
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function update_row_2($menu_id, $arg)
    {
        $row_arr = $arg;

        // 从session中取出数据
        $session = \Config\Services::session();
        $data_table = $session->get($menu_id.'-data_table');
        $primary_key = $session->get($menu_id.'-primary_key');
        $user_workid = $session->get('user_workid');

        $model = new Mcommon();

        $where = $this->get_where($row_arr, $primary_key);

        // 更新原记录
        $active_date = ''; //记录开始日期
        $change_fld_str = ''; //变更的表项
        $set = ''; //更新字段

        foreach ($row_arr as $row)
        {
            if ($row['fld_name'] == '记录开始日期')
            {
                $active_date = $row['value'];
            }

            $set_str = '';

            // 未修改的字段不处理
            if ($row['modified'] == false) continue;

            switch ($row['type'])
            {
                case '数值':
                    $set_str = sprintf('%s=%s', $row['fld_name'], $row['value']);
                    break;
                case '字符':
                case '日期':
                    $set_str = sprintf('%s=\'%s\'', $row['fld_name'], $row['value']);
                    break;
                default:
                    $set_str = sprintf('%s=\'%s\'', $row['fld_name'], $row['value']);
                    break;
            }

            if ($set != '')
            {
                $set = $set . ',';
                $change_fld_str = $change_fld_str . ',';
            }
            $set = $set . $set_str;
            $change_fld_str = $change_fld_str . $row['fld_name'];
        }

        // 更新操作记录、操作人员、有效标识等固定字段
        $set = sprintf('
            %s,记录结束日期="%s",操作记录="更新[2],%s",操作来源="页面",操作人员="%s",
            结束操作时间="%s",操作时间="%s",
            有效标识="0"', 
            $set, $active_date, $change_fld_str, $user_workid,
            date('Y-m-d H:i:s'), date('Y-m-d H:i:s') );

        // 插入新记录
        $read_str = '';
        $insert_str = '';

        $fields = $model->get_fields($data_table);
        foreach ($fields as $field)
        {
            if ($field == $primary_key || $field == '操作时间') continue;

            $fld_str = $field;
                
            // 字段处理
            switch ($field)
            {
                case '记录开始日期':
                    $fld_str = sprintf('\'%s\' as 记录开始日期', $active_date);
                    break;
                case '记录结束日期':
                    $fld_str = '"" as 记录结束日期';
                    break;
                case '操作记录':
                    $fld_str = '"" as 操作记录';
                    break;
                case '操作来源':
                    $fld_str = '"页面" as 操作来源';
                    break;
                case '操作人员':
                    $fld_str = sprintf('\'%s\' as 操作人员', $user_workid);
                    break;
                case '开始操作时间':
                    $fld_str = sprintf('\'%s\' as 开始操作时间', date('Y-m-d H:i:s'));
                    break;
                case '结束操作时间':
                    $fld_str = '"" as 结束操作时间';
                    break;
                case '校验标识':
                    $fld_str = '"0" as 校验标识';
                    break;
                case '删除标识':
                    $fld_str = '"0" as 删除标识';
                    break;
                case '有效标识':
                    $fld_str = '"1" as 有效标识';
                    break;
                default:
                    // 业务字段
                    foreach ($row_arr as $row)
                    {
                        if ($row['fld_name'] != $field) continue;
                        // 未修改的字段不处理
                        if ($row['modified'] == false) break;
        
                        // 更新字段处理
                        switch ($row['type'])
                        {
                            case '数值':
                                $fld_str = sprintf('%s as %s', $row['value'], $row['fld_name']);
                                break;
                            case '字符':
                            case '日期':
                                $fld_str = sprintf('\'%s\' as %s', $row['value'], $row['fld_name']);
                                break;
                            default:
                                $fld_str = sprintf('\'%s\' as %s', $row['value'], $row['fld_name']);
                                break;
                        }
                    }
                    break;
            }

            if ($read_str != '')
            {
                $read_str = $read_str . ',';
            }
            $read_str = $read_str . $fld_str;

            if ($insert_str != '')
            {
                $insert_str = $insert_str . ',';
            }
            $insert_str = $insert_str . $field;
        }

        $sql_select = sprintf('select %s from %s where %s', $read_str, $data_table, $where);

        // 插入新记录
        $sql_insert = sprintf('
            insert into %s (%s) %s', 
            $data_table, $insert_str, $sql_select);

        // 更新旧记录
        $sql_update = sprintf('update %s set %s where %s', $data_table, $set, $where);

        $model = new Mcommon();

        // 写日志
        $model->sql_log('更新[2]', $menu_id, sprintf('表名=`%s`,更新=`%s`,GUID=`%s`', $data_table, $change_fld_str, $primary_key));

        $num = $model->exec($sql_insert);
        $num = $model->exec($sql_update);
        return $num;
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 增加记录
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function add_row($menu_id='')
    {
        $request = \Config\Services::request();
        $row_arr = $request->getJSON(true);

        // 从session中取出数据
        $session = \Config\Services::session();
        $data_model = $session->get($menu_id.'-data_model');
        $before_insert = $session->get($menu_id.'-before_insert');
        $after_insert = $session->get($menu_id.'-after_insert');
        $primary_key = $session->get($menu_id.'-primary_key');

        $this->set_popup($menu_id, $row_arr);

        $model = new Mcommon();

        // 执行前处理
        if ($before_insert != '')
        {
            foreach ($row_arr as $row)
            {
                if (strpos($before_insert,$row['fld_name']) === false) continue;
                $before_insert = str_replace(sprintf('$%s',$row['fld_name']), $row['value'], $before_insert);
            }

            $arr = $model->select(sprintf('call %s', $before_insert))->getResultArray();
            foreach ($arr as $item)  // 非空,退出
            {
                foreach ($item as $key => $value)
                {
                    if ($value != '') exit($value);
                }
                break;
            }
        }

        $num = 0;
        switch ($data_model)
        {
            case '0': // 模式0,无额外字段
                $num = $this->add_row_0($menu_id, $row_arr);
                break;
            case '1': // 模式1,有额外字段
                $num = $this->add_row_1($menu_id, $row_arr);
                break;
            case '2': // 模式2,有额外字段
                $num = $this->add_row_2($menu_id, $row_arr);
                break;
            default:
                exit(sprintf('新增失败,数据模式[-%d-]错误',$data_model));
        }

        // 执行后处理
        if ($after_insert != '')
        {
            $key_str = $this->get_where($row_arr, $primary_key, 'str');
            $model->select(sprintf('call %s("新增","%s")', $after_insert, $key_str));
        }

        exit(sprintf('新增[%d]成功,新增 %d 条记录',$data_model,$num));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 增加记录,模式0,无额外字段
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function add_row_0($menu_id, $arg)
    {
        $row_arr = $arg;

        // 从session中取出数据
        $session = \Config\Services::session();
        $data_table = $session->get($menu_id.'-data_table');

        $flds_str = '';
        $values_str = '';
        foreach ($row_arr as $row)
        {
            if ($flds_str != '')
            {
                $flds_str = $flds_str . ',';
                $values_str = $values_str . ',';
            }
            $flds_str = $flds_str . $row['fld_name'];
            switch ($row['type'])
            {
                case '字符':
                case '日期':
                    $values_str = sprintf('%s"%s"', $values_str, $row['value']);
                    break;
                case '数值':
                    $values_str = sprintf('%s%.2f', $values_str, $row['value']);
                    break;
            }
        }

        $sql = sprintf('insert into %s (%s) values (%s)', $data_table, $flds_str, $values_str);

        $model = new Mcommon();

        // 写日志
        $model->sql_log('新增[0]', $menu_id, sprintf('表名=`%s`', $data_table));
        // 新增
        $num = $model->exec($sql);
        return $num;
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 增加记录,模式1,有额外字段 (原记录不变)
    // 额外字段:操作记录,操作来源,操作人员,操作时间,校验标识,删除标识,有效标识
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function add_row_1($menu_id, $arg)
    {
        $row_arr = $arg;

        // 从session中取出数据
        $session = \Config\Services::session();
        $data_table = $session->get($menu_id.'-data_table');
        $user_workid = $session->get('user_workid');

        $flds_str = '';
        $values_str = '';
        foreach ($row_arr as $row)
        {
            if ($flds_str != '')
            {
                $flds_str = $flds_str . ',';
                $values_str = $values_str . ',';
            }
            $flds_str = $flds_str . $row['fld_name'];
            switch ($row['type'])
            {
                case '字符':
                case '日期':
                    $values_str = sprintf('%s"%s"', $values_str, $row['value']);
                    break;
                case '数值':
                    $values_str = sprintf('%s%.2f', $values_str, $row['value']);
                    break;
            }
        }

        $sql = sprintf('
            insert into %s (%s,操作记录,操作来源,操作人员,开始操作时间,校验标识,删除标识,有效标识) 
            values (%s,"新增[1]","页面","%s","%s","0","0","1")',
            $data_table, $flds_str, $values_str, $user_workid, date('Y-m-d H:i:s'), date('Y-m-d H:i:s'));

        $model = new Mcommon();

        // 写日志
        $model->sql_log('新增[1]', $menu_id, sprintf('表名=`%s`', $data_table));
        // 新增
        $num = $model->exec($sql);
        return $num;
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 增加记录,模式2,有额外字段 (原记录更新,流水账模式)
    // 额外字段:操作记录,操作来源,操作人员,操作时间,校验标识,删除标识,有效标识,记录开始日期,记录结束日期
    // 比模式1多出:记录开始日期,记录结束日期
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function add_row_2($menu_id, $arg)
    {
        $row_arr = $arg;

        // 从session中取出数据
        $session = \Config\Services::session();
        $data_table = $session->get($menu_id.'-data_table');
        $user_workid = $session->get('user_workid');

        $flds_str = '';
        $values_str = '';
        foreach ($row_arr as $row)
        {
            if ($flds_str != '')
            {
                $flds_str = $flds_str . ',';
                $values_str = $values_str . ',';
            }
            $flds_str = $flds_str . $row['fld_name'];
            switch ($row['type'])
            {
                case '字符':
                case '日期':
                    $values_str = sprintf('%s"%s"', $values_str, $row['value']);
                    break;
                case '数值':
                    $values_str = sprintf('%s%.2f', $values_str, $row['value']);
                    break;
            }
        }

        $sql = sprintf('
            insert into %s (%s,记录开始日期,记录结束日期,操作记录,操作来源,操作人员,开始操作时间,操作时间,校验标识,删除标识,有效标识) 
            values (%s,"%s","","新增[2]","页面","%s","%s","%s","0","0","1")',
            $data_table, $flds_str, 
            $values_str, date('Y-m-d'), $user_workid, date('Y-m-d H:i:s'), date('Y-m-d H:i:s'));

        $model = new Mcommon();

        // 写日志
        $model->sql_log('新增[2]', $menu_id, sprintf('表名=`%s`', $data_table));
        // 新增
        $num = $model->exec($sql);
        return $num;
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 删除记录
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function delete_row($menu_id='')
    {
        $request = \Config\Services::request();
        $row_arr = $request->getJSON(true);

        // 从session中取出数据
        $session = \Config\Services::session();
        $data_model = $session->get($menu_id.'-data_model');

        switch ($data_model)
        {
            case '0': // 模式0,无额外字段
                $this->delete_row_0($menu_id, $row_arr);
                break;
            case '1': // 模式1,有额外字段
            case '2':
                $this->delete_row_1($menu_id, $row_arr);
                break;
            default:
                exit(sprintf('删除失败,数据模式[-%d-]错误',$data_model));
                break;
        }
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 删除记录,模式0,原记录直接删除
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function delete_row_0($menu_id, $arg)
    {
        // 从session中取出数据
        $session = \Config\Services::session();
        $data_table = $session->get($menu_id.'-data_table');
        $primary_key = $session->get($menu_id.'-primary_key');

        $where = $this->get_where($arg, $primary_key);

        $sql_delete = sprintf('delete from %s where %s', $data_table, $where);

        $model = new Mcommon();
        // 写日志
        $model->sql_log('删除[0]', $menu_id, sprintf('表名=`%s`,GUID=`%s`', $data_table, $primary_key));
        // 删除
        $num = $model->exec($sql_delete);
        exit(sprintf('删除[0]成功,删除 %d 条',$num));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 删除记录,模式1,额外字段更新,置删除标识为1,有效标识为0
    // 操作记录,操作来源,操作人员,操作时间,校验标识,删除标识,有效标识
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function delete_row_1($menu_id, $arg)
    {
        // 从session中取出数据
        $session = \Config\Services::session();
        $data_table = $session->get($menu_id.'-data_table');
        $primary_key = $session->get($menu_id.'-primary_key');
        $user_workid = $session->get('user_workid');

        $where = $this->get_where($arg, $primary_key);

        $sql_update = sprintf('
            update %s 
            set 操作记录="删除[1]",操作来源="页面",操作人员="%s",
                结束操作时间="%s",操作时间="%s",
                删除标识="1",有效标识="0"
            where %s',
            $data_table, $user_workid, 
            date('Y-m-d H:i:s'), date('Y-m-d H:i:s'),
            $where);

        $model = new Mcommon();

        // 写日志
        $model->sql_log('删除[1]', $menu_id, sprintf('表名=`%s`,GUID=`%s`', $data_table, $primary_key));
        // 更新
        $num = $model->modify($sql_update);

        exit(sprintf('删除[1]成功,删除 %d 条',$num));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 导出到xls
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function export($menu_id='')
    {
        // 从session中取出数据
        $session = \Config\Services::session();
        $query_str = $session->get($menu_id.'-query_str');
        $sp_name = $session->get($menu_id.'-sp_name');
        $sp_str = $session->get($menu_id.'-sp_str');

        $table = new \CodeIgniter\View\Table();

        $template = 
        [
            'cell_start' => '<td style="vnd.ms-excel.numberformat:@">',
            'cell_end' => '</td>',
            'cell_alt_start' => '<td style="vnd.ms-excel.numberformat:@">',
            'cell_alt_end' => '</td>'
        ];
        $table->setTemplate($template);

        $model = new Mcommon();

        if ($sp_name != '')
        {
            // 写日志
            $model->sql_log('sp导出', $menu_id, str_replace('"','',$sp_str));
            $query = $model->select($sp_str);
        }
        else
        {
            // 写日志
            $model->sql_log('导出', $menu_id, str_replace('"','',$query_str));
            $query = $model->select($query_str);
        }

        header('content-type:application/vnd.ms-excel; charset=gbk');
        header('content-disposition:attachment;filename=aa.xls');
        header('Accept-Ranges: bytes');

        exit($table->generate($query));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 修改密码
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function change_pswd($from='', $title='')
    {
        if ($from == 'front')
        {
            $new_pswd = $this->request->getPost()['pswd_1'];

            // 从session中取出数据
            $session = \Config\Services::session();
            $user_workid = $session->get('user_workid');
            $user_pswd = $session->get('user_pswd');

            $model = new Mcommon();

            // 写日志
            $model->sql_log('修改密码', '', $user_pswd.'-->'.$new_pswd);

            if ($new_pswd == $user_pswd)
            {
                echo '新密码与旧密码相同, 未作修改';
                return;
            }

            $sql = sprintf('update def_user set 密码="%s" where 工号="%s"', 
                $new_pswd, $user_workid);

            if($model->exec($sql)>0)
            {
                echo '密码修改成功';
                return;
            }
            else
            {
                echo '密码修改失败, 请联系技术人员';
                return;
            }
        }
        else
        {
            $send['title'] = ($title=='') ? '修改密码' : $title;
            $send['next_page'] = 'Frame/change_pswd/front';

            echo view('Vpassword.php', $send);
        }
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 内部函数,取出where条件
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function get_where($row_arr, $primary_key, $rc_type='where')
    {
        $where = '';
        $value_str = '';
        foreach ($row_arr as $row)
        {
            //主键
            if ($row['fld_name'] == $primary_key)
            {
                $value_str = $row['value'];
                $key_arr = explode(',', $row['value']);

                $key_str = '';
                foreach ($key_arr as $key)
                {
                    if ($key_str == '')
                    {
                        $key_str = sprintf('\'%s\'', $key);
                    }
                    else
                    {
                        $key_str = sprintf('%s,\'%s\'', $key_str, $key);
                    }
                }

                $where = sprintf('%s in (%s)', $row['fld_name'], $key_str);
                continue;
            }
        }

        return ($rc_type == 'where') ? $where : $value_str;
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 内部函数,取出弹窗参数
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function get_popup($menu_id)
    {
        $popup_grid_arr = [];
        $popup_obj_arr = [];
        $model = new Mcommon();

        // 取出赋值类型为弹窗的信息
        $menu_sql = sprintf(
            'select 赋值类型,对象,对象名称,对象表名
            from view_function
            where 赋值类型="弹窗" and 功能编码="%s"
            group by 对象名称', $menu_id);

        $menu_rows = $model->select($menu_sql)->getResult();
        foreach ($menu_rows as $menu_row)
        {
            $obj_sql = sprintf(
                'select 对象名称,本级编码,本级名称,本级全称,本级级别名称,本级级别,
                    上级编码,上级名称,上级全称,上级级别名称,最大级别,
                    本级初始值
                from %s
                order by 对象名称,本级级别,本级全称', 
                $menu_row->对象表名);
            $obj_rows = $model->select($obj_sql)->getResult();

            if (array_key_exists($menu_row->对象名称, $popup_obj_arr) == false)
            {
                $popup_obj_arr[$menu_row->对象名称] = [];
                $popup_grid_arr[$menu_row->对象名称] = [];
            }

            foreach ($obj_rows as $obj_row)
            {
                if (array_key_exists($obj_row->本级级别名称, $popup_obj_arr[$menu_row->对象名称]) == false)
                {
                    $popup_obj_arr[$menu_row->对象名称][$obj_row->本级级别名称] = [];
                    $popup_obj_arr[$menu_row->对象名称][$obj_row->本级级别名称]['本级级别'] = $obj_row->本级级别;
                    $popup_obj_arr[$menu_row->对象名称][$obj_row->本级级别名称]['本级初始值'] = $obj_row->本级初始值;
                    $popup_obj_arr[$menu_row->对象名称][$obj_row->本级级别名称]['上级级别名称'] = $obj_row->上级级别名称;

                    // 前端popup_grid数据
                    array_push($popup_grid_arr[$menu_row->对象名称], array('表项'=>$obj_row->本级级别名称, '级别'=>$obj_row->本级级别, '取值'=>$obj_row->本级初始值));
                }
                if (array_key_exists($obj_row->上级名称, $popup_obj_arr[$menu_row->对象名称][$obj_row->本级级别名称]) == false)
                {
                    $popup_obj_arr[$menu_row->对象名称][$obj_row->本级级别名称][$obj_row->上级名称] = [];
                }
                array_push($popup_obj_arr[$menu_row->对象名称][$obj_row->本级级别名称][$obj_row->上级名称], $obj_row->本级名称);
            }
        }

        return array($popup_grid_arr, $popup_obj_arr);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 内部函数,设置弹窗参数
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function set_popup($menu_id, &$row_arr)
    {
        $session = \Config\Services::session();
        $columns_arr = $session->get($menu_id.'-columns_arr');

        foreach ($columns_arr as $column)
        {
            if ($column['赋值类型'] == '弹窗')
            {
                $popup_value = '';
                for ($ii=0; $ii<count($row_arr); $ii++)
                {
                    if ($row_arr[$ii]['modified'] == false || $row_arr[$ii]['col_name'] != $column['列名'] || $row_arr[$ii]['value'] == '') continue;

                    $popup_arr = explode(',', $row_arr[$ii]['value']);
                    foreach ($popup_arr as $popup)
                    {
                        $arr = explode('^', $popup);
                        if (strpos($column['对象'],'编码') !== false)
                        {
                            $popup_value = ($popup_value=='') ? $arr[0] : $popup_value.','.$arr[0];
                        }
                        else if (strpos($column['对象'],'全称') !== false)
                        {
                            $popup_value = ($popup_value=='') ? $arr[1] : $popup_value.','.$arr[1];
                        }
                    }

                    $row_arr[$ii]['value'] = $popup_value;
                }
            }
        }
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 弹窗输入校验
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function verify_popup($menu_id='')
    {
        $request = \Config\Services::request();
        $arg = $request->getJSON(true);

        // 从session中取出数据
        $session = \Config\Services::session();
        $columns_arr = $session->get($menu_id.'-columns_arr');

        $obj_table = '';

        foreach ($columns_arr as $col)
        {
            if ($col['列名'] != $arg['列名']) continue;
            $obj_table = $col['对象表名'];
        }

        $sql = sprintf(
            'select 对象名称,本级编码,本级名称,本级全称,本级级别名称,本级级别,
                上级编码,上级名称,上级全称,上级级别名称,最大级别,
                本级初始值
            from %s
            where 本级全称="%s"
            order by 对象名称,本级级别,本级全称', 
            $obj_table, $arg['本级全称']);

            $model = new Mcommon();
            $rows = $model->select($sql)->getResultArray();

        exit(sprintf('%s^%s', $rows[0]['本级编码'], $rows[0]['本级全称']));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 执行数据整理
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function upkeep($menu_id='')
    {
        // 从session中取出数据
        $session = \Config\Services::session();
        $data_upkeep = $session->get($menu_id.'-data_upkeep');

        $model = new Mcommon();
        $model->select(sprintf('call %s', $data_upkeep));

        exit('执行成功');
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 整表操作,未完成(2024-5-24)
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function update_table($menu_id='')
    {
        $request = \Config\Services::request();
        $arg = $request->getJSON(true);

        // 从session中取出数据
        $session = \Config\Services::session();
        $columns_arr = $session->get($menu_id.'-columns_arr');
        $data_table = $session->get($menu_id.'-data_table');

        $model = new Mcommon();

        $fields = $model->get_fields($data_table);
        foreach ($fields as $field)
        {
        }
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 图形钻取
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function chart_drill($menu_id='')
    {
        $request = \Config\Services::request();
        $drill_arr = $request->getJSON(true);

        // 从session中取出数据
        $session = \Config\Services::session();
        $chart_drill_arr = $session->get($menu_id.'-chart_drill_arr');

        $model = new Mcommon();

        // 生成前端图形数据
        $arr = explode('^', $drill_arr[2]['SID']);

        if ($chart_drill_arr == [])
        {
            $chart_arr = $this->get_chart_data($menu_id, $arr[0], $arr[1], []);
        }
        else
        {
            $chart_arr = $chart_drill_arr;
        }

        $sql = sprintf('
            select t1.图形模块,图形编号,图形名称,图形类型,
                查询表名,查询字段,查询条件,汇总条件,排序条件,
                字段模块,页面布局,钻取模块,条件叠加,顺序,
                钻取字段,钻取条件
            from
            (
                select 图形模块,图形编号,图形名称,图形类型,
                    查询表名,查询字段,属地字段,查询条件,汇总条件,排序条件,
                    字段模块,页面布局,钻取模块,条件叠加,顺序
                from def_chart_config
                where 有效标识="1"
            ) as t1
            left join
            (
                select tb.图形模块,tb.钻取字段,tb.钻取条件
                from
                (
                    select 图形模块,图形编号,图形名称,图形类型,
                        查询表名,查询字段,属地字段,查询条件,汇总条件,排序条件,
                        字段模块,页面布局,钻取模块,条件叠加,顺序
                    from def_chart_config
                    where 有效标识="1" and 图形模块="%s" and 图形编号="%s"
                ) as ta
                left join
                (
                    select *
                    from def_chart_drill_config
                ) as tb on ta.钻取模块=tb.钻取模块
                where ta.图形模块="%s" 
                and ta.图形编号="%s"
                and tb.钻取选项="%s"
            ) as t2 on t1.图形模块=t2.图形模块
            having t2.钻取字段 is not null', 
            $arr[0], $arr[1], $arr[0], $arr[1], $drill_arr[1]['钻取选项']);

        $arr = $model->select($sql)->getResultArray()[0];

        $drill_where = array('条件'=>'', '副标题'=>'');
        if ($arr['钻取条件'] != '')
        {
            $col_arr = explode(';', $arr['钻取字段']);
            $drill_where['条件'] = str_replace('`', '"', $arr['钻取条件']);

            foreach ($drill_arr[2] as $key => $value)
            {
                $drill_where['条件'] = str_replace(sprintf('$%s',$key), $value, $drill_where['条件']);

                if(strpos($arr['钻取字段'], $key) !== false)
                {
                    $drill_where['副标题'] = ($drill_where['副标题']=='') ? $value : $drill_where['副标题'].','.$value;
                }
            }
        }

        // 生成钻取图形数据
        $arr = $this->get_chart_data($menu_id, $arr['图形模块'], $arr['图形编号'], $drill_where);
        foreach ($arr as $key => $value)
        {
            if (array_key_exists($key, $chart_arr) == false)
            {
                $chart_arr[$key] = [];
            }
            $chart_arr[$key] = $value;
        }

        // 存入session
        $session_arr = [];
        $session_arr[$menu_id.'-chart_drill_arr'] = $chart_arr;
        $session = \Config\Services::session();
        $session->set($session_arr);

        exit(json_encode($chart_arr));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 取出前端图形数据
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function get_chart_data($menu_id, $chart_id, $chart_code='', $front_where=[])
    {
        $model = new Mcommon();

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_debug_authz = $session->get('user_debug_authz');
        $user_location_str = $session->get('user_location_str');
        $location_authz_cond = $session->get($menu_id.'-location_authz_cond');
        $dept_authz_cond = $session->get($menu_id.'-dept_authz_cond');
        $chart_drill_cond_str = $session->get(sprintf('%s^%s-chart_drill_cond_str',$menu_id,$chart_id));
        $chart_drill_title_str = $session->get(sprintf('%s^%s-chart_drill_title_str',$menu_id,$chart_id));

        $drill_where = ($front_where == []) ? '' : $front_where['条件'];
        $drill_title = ($front_where == []) ? '' : $front_where['副标题'];

        $chart_arr = [];

        if ($chart_code == '')  // 图形初始
        {
            $sql = sprintf('
                select 图形模块,图形编号,图形名称,图形类型,
                    查询表名,查询字段,属地字段,查询条件,汇总条件,排序条件,记录条数,
                    字段模块,页面布局,钻取模块,条件叠加,顺序
                from def_chart_config
                where 有效标识="1" and 图形模块="%s" and 顺序>0
                order by 图形模块,图形编号,顺序', 
                $chart_id);
        }
        else  // 图形钻取
        {
            $sql = sprintf('
                select 图形模块,图形编号,图形名称,图形类型,
                    查询表名,查询字段,属地字段,查询条件,汇总条件,排序条件,记录条数
                    字段模块,页面布局,钻取模块,条件叠加,顺序
                from def_chart_config
                where 有效标识="1" and 图形模块="%s" and 图形编号="%s" and 顺序>0
                order by 图形模块,图形编号,顺序', 
                $chart_id, $chart_code);
        }

        $query = $model->select($sql);
        $results = $query->getResult();
        foreach ($results as $row)
        {
            $data_sql = sprintf('select %s, "%s^%s" as SID from %s', 
                $row->查询字段, $row->图形模块, $row->图形编号, $row->查询表名);

            $where = '';
            // 条件语句加上部门授权条件
            if ($dept_authz_cond != '')
            {
                $where = ($where == '') ? $dept_authz_cond : $where . ' and ' . $dept_authz_cond;
            }
            // 条件语句加上属地条件
            if($row->属地字段 != '')
            {
                $where = ($where == '') ? $location_authz_cond : $where . ' and ' . $location_authz_cond;
            }
            if ($row->查询条件 != '')
            {
                if ($where != '')
                {
                    $where = sprintf('(%s) and (%s)', $where, $row->查询条件);
                }
                else
                {
                    $where = $row->查询条件;
                }
            }
            if ($chart_drill_cond_str != '' && $row->条件叠加 == '1')
            {
                if ($where != '')
                {
                    $where = sprintf('(%s) and (%s)', $where, $chart_drill_cond_str);
                }
                else
                {
                    $where = $chart_drill_cond_str;
                }
            }
            if ($drill_where != '')
            {
                if ($where != '')
                {
                    $where = sprintf('(%s) and (%s)', $where, $drill_where);
                }
                else
                {
                    $where = $drill_where;
                }
            }
 
            // 生成叠加的钻取条件
            if ($row->条件叠加 == '1')
            {
                if ($chart_drill_cond_str != '')
                {
                    if ($drill_where != '')
                    {
                        $chart_drill_cond_str = sprintf('(%s) and (%s)', $chart_drill_cond_str, $drill_where);
                    }
                }
                else
                {
                    if ($drill_where != '')
                    {
                        $chart_drill_cond_str = $drill_where;
                    }
                }
            }

            // 生成副标题
            if ($drill_title != '')
            {
                if ($chart_drill_title_str != '')
                {
                    $chart_drill_title_str = sprintf('%s,%s', $chart_drill_title_str,$drill_title);
                }
                else
                {
                    $chart_drill_title_str = $drill_title;
                }
            }

            if ($where != '')
            {
                $data_sql = sprintf('%s where %s', $data_sql, $where);
            }
            if ($row->汇总条件 != '')
            {
                $data_sql = sprintf('%s group by %s', $data_sql, $row->汇总条件);
            }
            if ($row->排序条件 != '')
            {
                $data_sql = sprintf('%s order by %s', $data_sql, $row->排序条件);
            }
            if ($row->记录条数 > 0)
            {
                $data_sql = sprintf('%s limit %d', $data_sql, $row->记录条数);
            }

            $chart_data = $model->select($data_sql)->getResult();

            if ($chart_data == [])
            {
                continue;
            }

            // 图形数据
            if (array_key_exists($row->图形模块, $chart_arr) == false)
            {
                $chart_arr[$row->图形模块] = [];
            }
            if (array_key_exists($row->图形编号, $chart_arr[$row->图形模块]) == false)
            {
                $chart_arr[$row->图形模块][$row->图形编号] = [];
            }

            $chart_arr[$row->图形模块][$row->图形编号]['图形模块'] = $row->图形模块;
            $chart_arr[$row->图形模块][$row->图形编号]['图形编号'] = $row->图形编号;
            $chart_arr[$row->图形模块][$row->图形编号]['图形名称'] = ($chart_drill_title_str == '') ? $row->图形名称 : sprintf('%s(%s)', $row->图形名称, $chart_drill_title_str);
            $chart_arr[$row->图形模块][$row->图形编号]['图形类型'] = $row->图形类型;
            $chart_arr[$row->图形模块][$row->图形编号]['字段模块'] = $row->字段模块;
            $chart_arr[$row->图形模块][$row->图形编号]['页面布局'] = $row->页面布局;
            $chart_arr[$row->图形模块][$row->图形编号]['字段'] = [];
            $chart_arr[$row->图形模块][$row->图形编号]['字段数'] = 0;
            $chart_arr[$row->图形模块][$row->图形编号]['数据'] = $chart_data;
            $chart_arr[$row->图形模块][$row->图形编号]['钻取模块'] = [];

            $data_sql = str_replace('\'','~~',$data_sql);
            $data_sql = str_replace('"','~~',$data_sql);
            $chart_arr[$row->图形模块][$row->图形编号]['SQL'] = ($user_debug_authz=='1') ? $data_sql : '';

            // 图形列信息
            $col_sql = sprintf('
                select 字段模块,列名,字段名,坐标轴,图形类型
                from def_chart_column
                where 字段模块="%s" and 顺序>0
                order by 字段模块,顺序', $row->字段模块);

            $col_results = $model->select($col_sql)->getResult();
            foreach ($col_results as $col_row)
            {
                if (array_key_exists($col_row->字段名, $chart_arr[$row->图形模块][$row->图形编号]['字段']) == false)
                {
                    $chart_arr[$row->图形模块][$row->图形编号]['字段'][$col_row->字段名] = [];
                }
                $chart_arr[$row->图形模块][$row->图形编号]['字段数'] += 1;
                $chart_arr[$row->图形模块][$row->图形编号]['字段'][$col_row->字段名]['列名'] = $col_row->列名;
                $chart_arr[$row->图形模块][$row->图形编号]['字段'][$col_row->字段名]['字段名'] = $col_row->字段名;
                $chart_arr[$row->图形模块][$row->图形编号]['字段'][$col_row->字段名]['坐标轴'] = $col_row->坐标轴;
                $chart_arr[$row->图形模块][$row->图形编号]['字段'][$col_row->字段名]['图形类型'] = $col_row->图形类型;
            }

            // 钻取模块
            $drill_sql = sprintf('
                select 钻取模块,钻取选项,钻取字段,钻取条件,图形模块
                from def_chart_drill_config
                where 钻取模块="%s" and 顺序>0
                order by 钻取模块,顺序', $row->钻取模块);

            $drill_results = $model->select($drill_sql)->getResult();
            foreach ($drill_results as $drill_row)
            {
                if (array_key_exists($drill_row->钻取选项, $chart_arr[$row->图形模块][$row->图形编号]['钻取模块']) == false)
                {
                    $chart_arr[$row->图形模块][$row->图形编号]['钻取模块'][$drill_row->钻取选项] = [];
                }

                $chart_arr[$row->图形模块][$row->图形编号]['钻取模块'][$drill_row->钻取选项]['钻取选项'] = $drill_row->钻取选项;
                $chart_arr[$row->图形模块][$row->图形编号]['钻取模块'][$drill_row->钻取选项]['钻取字段'] = $drill_row->钻取字段;
                $chart_arr[$row->图形模块][$row->图形编号]['钻取模块'][$drill_row->钻取选项]['钻取条件'] = $drill_row->钻取条件;
                $chart_arr[$row->图形模块][$row->图形编号]['钻取模块'][$drill_row->钻取选项]['图形模块'] = $drill_row->图形模块;

                $chart_arr[$row->图形模块][$row->图形编号]['钻取模块'][$drill_row->钻取选项]['继承条件'] = $chart_drill_cond_str;
                $chart_arr[$row->图形模块][$row->图形编号]['钻取模块'][$drill_row->钻取选项]['继承标题'] = $chart_drill_title_str;

                // 存入session
                $session_arr = [];
                $session_arr[sprintf('%s^%s-chart_drill_cond_str',$menu_id,$drill_row->图形模块)] = $chart_drill_cond_str;
                $session_arr[sprintf('%s^%s-chart_drill_title_str',$menu_id,$drill_row->图形模块)] = $chart_drill_title_str;
                $session = \Config\Services::session();
                $session->set($session_arr);
            }
        }

        return $chart_arr;
    }
}