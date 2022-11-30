<?php
/* v6.1.1.1.202211300950, from office */
namespace App\Controllers;
use \CodeIgniter\Controller;
use App\Models\Mcommon;

class Frame extends Controller
{
    public function __construct()
    {
        helper(['form', 'url']);
    }

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
        $user_role = $session->get('user_role');

        str_replace(' ', '', $user_role);
        str_replace('，', ',', $user_role);
        $role_arr = explode(',', $user_role);

        $role_str = '';
        foreach ($role_arr as $role)
        {
            if ($role_str == '')
            {
                $role_str = sprintf('"%s"', $role);
            }
            else
            {
                $role_str = sprintf('%s,"%s"', $role_str ,$role);
            }
        }

        $sql = sprintf(
            'select 
                t1.角色编号,t1.角色名称,t1.功能赋权,t1.部门赋权,
                t1.新增授权,t1.修改授权,t1.删除授权,t1.导入授权,t1.导出授权,
                t2.功能编码,t2.一级菜单,t2.二级菜单,t2.功能模块,t2.查询模块,
                t2.菜单顺序,t2.菜单显示,
                ifnull(t3.部门字段,"") as 部门字段,
                ifnull(t3.属地字段,"") as 属地字段
            from def_role as t1
            left join
            (
                select 功能编码,一级菜单,二级菜单,功能模块,查询模块,
                    部门字段,菜单顺序,菜单显示
                from def_function
                where 菜单顺序>0
            ) as t2 on t1.功能赋权=t2.功能编码
            left join def_query_config as t3 on t2.查询模块=t3.查询模块
            where t1.角色编号 in (%s)
            group by t1.功能赋权
            order by t2.菜单顺序', $role_str);

        $model = new Mcommon();
        $query = $model->select($sql);
        $results = $query->getResult();

        $json = [];

        $authz = [];
        foreach ($results as $row)
        {
            // 部门访问权限设置
            $dept_str = $row->部门赋权;
            $dept_fld = $row->部门字段;
            str_replace(' ', '' , $dept_str);
            str_replace('，', ',' , $dept_str);
            $dept_arr = explode(',', $dept_str);

            $dept_cond = '';
            foreach ($dept_arr as $dept)
            {
                if ($dept == '' || $dept_fld == '')  //优化
                {
                    break;
                }
                if ($dept_cond == '')
                {
                    $dept_cond = sprintf('instr(%s,"%s")', $dept_fld, $dept);
                }
                else
                {
                    $dept_cond = sprintf('%s or instr(%s,"%s")', $dept_cond, $dept_fld, $dept);
                }
            }

            // 存入session
            $session_arr = [];
            $session_arr['user_role_str'] = $role_str;
            $session_arr[$row->功能赋权.'-dept_authz'] = $row->部门赋权;
            $session_arr[$row->功能赋权.'-dept_cond'] = $dept_cond;
            $session_arr[$row->功能赋权.'-dept_fld'] = $row->部门字段;
            $session_arr[$row->功能赋权.'-menu_1'] = $row->一级菜单;
            $session_arr[$row->功能赋权.'-menu_2'] = $row->二级菜单;
            $session_arr[$row->功能赋权.'-add_authz'] = $row->新增授权;
            $session_arr[$row->功能赋权.'-modify_authz'] = $row->修改授权;
            $session_arr[$row->功能赋权.'-delete_authz'] = $row->删除授权;
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
        $dept_cond = $session->get($menu_id.'-dept_cond');
        //$dept_fld = $session->get($menu_id.'-dept_fld');
        $user_role_str = $session->get('user_role');
        $user_location = $session->get('user_location');
        $add_authz = $session->get($menu_id.'-add_authz');
        $modify_authz = $session->get($menu_id.'-modify_authz');
        $delete_authz = $session->get($menu_id.'-delete_authz');
        $import_authz = $session->get($menu_id.'-import_authz');
        $export_authz = $session->get($menu_id.'-export_authz');
        $caller_func_condition = $session->get($menu_id.'-caller_func_condition_'.$front_id);

        //长时间不操作,session失效
        if ($user_workid == '')
        {
            $Arg['NextPage'] = base_url('login/checkin');
            echo view('Vlogin.php', $Arg);
            return;
        }

        // 处理钻取功能相关数据
        $cond_arr = json_decode($front_where);
        $front_where = [];
        $caller_col_arr = [];

        if ($front_id != '')
        {
            if ($caller_func_condition != '')
            {
                $caller_col = [];
                $col_arr = explode(';', $caller_func_condition);
                foreach ($col_arr as $col_str)
                {
                    if (strpos($col_str,'^') != false)
                    {
                        $arr = explode('^', $col_str);
                        $caller_col['caller_col'] = $arr[0];
                        $caller_col['called_col'] = $arr[1];
                    }
                    else
                    {
                        $caller_col['caller_col'] = $col_str;
                        $caller_col['called_col'] = $col_str;
                    }
                    array_push($caller_col_arr, $caller_col);
                }
            }

            foreach ($cond_arr as $key => $value)
            {
                foreach ($caller_col_arr as $col_arr)
                {
                    if ($key != $col_arr['caller_col']) continue;
                    $front_where[$col_arr['called_col']] = $value;
                }
            }
        }

        $primary_key = '';

        $data_col_arr = [];  // 前端data_grid列信息,用于显示
        $columns_arr = [];  // 列信息
        $send_columns_arr = []; // 传递到前端的列信息,查询名为公式,前端报错
        $tb_arr = [];  // 控制菜单栏

        $update_value_arr = [];  // 前端update_grid值信息,用于显示
        $cond_value_arr = [];  // 条件设置信息

        $tip_column = '';  // 前端foot显示的字段

        // 前端data_grid列信息,手工增加选取列和序号列
        $data_col_arr['选取']['field'] = '选取';
        $data_col_arr['选取']['width'] = 100;
        $data_col_arr['选取']['resizable'] = true;
        $data_col_arr['选取']['headerCheckboxSelection'] = true;
        $data_col_arr['选取']['checkboxSelection'] = true;

        $data_col_arr['序号']['field'] = '序号';
        $data_col_arr['序号']['type'] = 'numericColumn';
        $data_col_arr['序号']['width'] = 90;
        $data_col_arr['序号']['resizable'] = true;
        $data_col_arr['序号']['sortable'] = true;

        $object_arr = [];  // 下拉选择的对象值

        // 读出列配置信息
        $sql = sprintf('
            select 功能编码,查询模块,字段模块,部门字段,属地字段,
                列名,列类型,列宽度,字段名,查询名,对象,
                可修改,可筛选,可新增,主键,赋值类型,
                提示条件,提示样式设置,异常条件,异常样式设置,列顺序
            from view_function
            where 功能编码=%s and 列顺序>0
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
            $arr['可修改'] = $row->可修改;
            $arr['可新增'] = $row->可新增;
            $arr['提示条件'] = $row->提示条件;
            $arr['提示样式'] = $row->提示样式设置;
            $arr['异常条件'] = $row->异常条件;
            $arr['异常样式'] = $row->异常样式设置;

            array_push($columns_arr, $arr);

            $arr['查询名'] = '';
            array_push($send_columns_arr, $arr);

            if ($row->提示条件 == 1)
            {
                $tip_column = $row->列名;
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

            // 主键不能更改
            if ($row->主键 == 1) continue;

            // 前端update_grid值
            if ($row->可新增 != 1) continue;

            $value_arr = [];
            $value_arr['列名'] = $row->列名;
            $value_arr['字段名'] = $row->字段名;
            $value_arr['列类型'] = $row->列类型;
            $value_arr['取值'] = '';

            if ($row->赋值类型 == '下拉')
            {
                $object_arr[$row->列名] = [];
                $object_arr[$row->列名][0] = '';

                $obj_sql = sprintf('
                    select 对象值 
                    from def_object 
                    where 对象名称="%s"
                        and (属地="" or instr(属地,"%s"))
                    order by convert(对象值 using gbk)',
                    $row->对象, $user_location);

                $qry = $model->select($obj_sql);
                $rslt = $qry->getResult();
                foreach($rslt as $vv)
                {
                    array_push($object_arr[$row->列名], $vv->对象值);
                }
            }

            array_push($update_value_arr, $value_arr);

            // 前端要显示的cond_grid条件信息
            $cond = [];
            $cond['列名'] = $row->列名;
            $cond['字段名'] = $row->字段名;
            $cond['列类型'] = $row->列类型;
            $cond['汇总条件'] = '';
            $cond['平均'] = '';
            $cond['条件1'] = '';
            $cond['参数1'] = '';
            $cond['条件关系'] = '';
            $cond['条件2'] = '';
            $cond['参数2'] = '';
            $cond['计算方式'] = '';

            array_push($cond_value_arr, $cond);

            /*
            // 匹配钻取条件
            if ($front_id == '') continue;
            foreach ($front_where as $key => $value)
            {
                if ($key != $row->列名) continue;

                switch ($row->列类型)
                {
                    case '字符':
                    case '日期':
                        $front_where[$key] = sprintf('%s="%s"', $row->查询名, $value);
                        break;
                    case '数值':
                        $front_where[$key] = sprintf('%s=%s', $row->查询名, $value);
                        break;
                }
            }
            */
        }

        // 匹配钻取条件(没有获得字段类型,数值会有bug)
        if ($front_id != '')
        {
            foreach ($front_where as $key => $value)
            {
                $front_where[$key] = sprintf('%s="%s"', $key, $value);
            }
        }

        // 取出查询模块对应的表配置
        $where = '';
        $group = '';
        $order = '';
        $query_table = '';
        $dept_fld = '';  //部门字段
        $location_fld = '';  //属地字段
        $query_where = '';
        $query_group = '';
        $query_order = '';
        $next_func_id = '';
        $next_func_name = '';
        $next_func_condition = '';
        $import_func_id = '';
        $result_count = '';
        $update_table = '';
        $update_module = '';

        $sql = sprintf('
            select 
                t1.功能编码,
                查询表名,
                更新表名,更新模块,
                部门字段,属地字段,
                查询条件,汇总条件,排序条件,初始条数,
                钻取模块,钻取条件,
                ifnull(t2.钻取名称,"") as 钻取名称,
                导入模块,
                ifnull(t3.导入名称,"") as 导入名称
            from view_function as t1
            left join 
            (
                select 功能编码,二级菜单 as 钻取名称
                from view_function
                group by 功能编码
            ) as t2 on t1.钻取模块=t2.功能编码
            left join 
            (
                select 功能编码,二级菜单 as 导入名称
                from view_function
                group by 功能编码
            ) as t3 on t1.导入模块=t3.功能编码
            where t1.功能编码=%s
            group by t1.功能编码', $menu_id);

        $query = $model->select($sql);
        $results = $query->getResult();
        foreach ($results as $row)
        {
            $query_table = $row->查询表名;
            $result_count = $row->初始条数;
            $dept_fld = $row->部门字段;
            $location_fld = $row->属地字段;

            $query_where = $row->查询条件;
            if(strpos($row->查询条件, '$角色') != false)
            {
                $query_where = str_replace('$角色', $user_role_str, $row->查询条件);
            }

            $query_group = $row->汇总条件;
            $query_order = $row->排序条件;

            $next_func_id = $row->钻取模块;
            $next_func_name = $row->钻取名称;

            $next_func_condition = $row->钻取条件;
            str_replace(' ', '', $next_func_condition);
            str_replace('；', ';', $next_func_condition);

            $import_func_id = $row->导入模块;
            $import_func_name = $row->导入名称;

            $update_table = $row->更新表名;
            $update_module = $row->更新模块;
            break;
        }

        $tb_arr['钻取授权'] = ($next_func_id!='') ? true : false;
        $tb_arr['导入授权'] = ($import_func_id!='') ? true : false;

        // 拼出查询语句
        $select_str = '';
        foreach ($columns_arr as $column) 
        {
            if ($select_str != '')
            {
                $select_str = $select_str . ',';
            }
            $select_str = sprintf('%s %s as `%s`', $select_str, $column['查询名'], $column['列名']);
        }

        $sql = sprintf('select "" as 选取,(@i:=@i+1) as 序号,%s 
            from %s,(select @i:=0) as xh', 
            $select_str, $query_table);

        // 加上初始查询条件
        if ($query_where != '')
        {
            $where = $query_where;
        }

        // 条件语句加上部门授权条件
        if ($dept_cond)
        {
            $where = ($where == '') ? $dept_cond : $where . ' and ' . $dept_cond;
        }

        // 条件语句加上属地条件
        if ($location_fld != '')
        {
            $location_cond = sprintf('%s="%s"', $location_fld, $user_location);
            $where = ($where == '') ? $location_cond : $where . ' and ' . $location_cond;
        }

        // 数据钻取,条件语句加上前端选定的条件
        if ($front_where != '')
        {
            foreach ($front_where as $key => $value)
            {
                $where = ($where == '') ? $value : sprintf('%s and %s',$where,$value);
            }
        }

        if ($where != '')
        {
            $sql = sprintf('%s where %s', $sql, $where);
        }

        // 加上group by 条件
        if ($query_group != '')
        {
            $group = $query_group;
            $sql = sprintf('%s group by %s', $sql, $group);
        }

        // 加上order by
        if ($query_order != '')
        {
            $order = $query_order;
            $sql = sprintf('%s order by %s', $sql, $order);
        }

        // 加上初始结果条数
        if ($result_count > 0)
        {
            $sql = sprintf('%s limit %d', $sql, $result_count);
        }

        // 写日志
        $model->sql_log('查询', $menu_id, sprintf('表名=%s,条件=%s', $query_table, str_replace('"','',$where)));

        // 读出数据
        $query = $model->select($sql);
        $results = $query->getResult();

        // 存入session
        $session_arr = [];
        $session_arr[$menu_id.'-select_str'] = $select_str;
        $session_arr[$menu_id.'-query_str'] = $sql;
        $session_arr[$menu_id.'-query_table'] = $query_table;
        $session_arr[$menu_id.'-columns_arr'] = $columns_arr;
        $session_arr[$menu_id.'-primary_key'] = $primary_key;
        $session_arr[$menu_id.'-back_where'] = $where;
        $session_arr[$menu_id.'-back_group'] = $group;
        $session_arr[$menu_id.'-back_order'] = $order;

        if ($next_func_id != '')
        {
            $session_arr[$menu_id.'-next_func_id'] = $next_func_id;
            $session_arr[$menu_id.'-next_func_name'] = $next_func_name;
            $session_arr[$menu_id.'-next_func_condition'] = $next_func_condition;

            $session_arr[$next_func_id.'-caller_func_id_'.$menu_id] = $menu_id;
            $session_arr[$next_func_id.'-caller_func_condition_'.$menu_id] = $next_func_condition;
        }

        $session_arr[$menu_id.'-import_func_id'] = $import_func_id;
        $session_arr[$menu_id.'-import_func_name'] = $import_func_name;
        $session_arr[$menu_id.'-update_table'] = $update_table;
        $session_arr[$menu_id.'-update_module'] = $update_module;

        $session = \Config\Services::session();
        $session->set($session_arr);

        $tb_arr['新增授权'] = ($add_authz=='1') ? true : false ;
        $tb_arr['修改授权'] = ($modify_authz=='1') ? true : false ;
        $tb_arr['删除授权'] = ($delete_authz=='1') ? true : false ;
        $tb_arr['导入授权'] = ($import_authz=='1' && $import_func_id!='') ? true : false ;
        $tb_arr['导出授权'] = ($export_authz=='1') ? true : false ;

        //返回页面
        $send['toolbar_json'] = json_encode($tb_arr);
        $send['columns_json'] = json_encode($send_columns_arr);
        $send['data_col_json'] = json_encode($data_col_arr);
        $send['data_value_json'] = json_encode($results);
        $send['update_module'] = $update_module;
        $send['update_value_json'] = json_encode($update_value_arr);
        $send['cond_value_json'] = json_encode($cond_value_arr);
        $send['object_json'] = json_encode($object_arr);
        $send['func_id'] = $menu_id;
        $send['primary_key'] = $primary_key;
        $send['back_where'] = strtr($where, '"', '');
        $send['back_group'] = $group;
        $send['next_func_id'] = $next_func_id;
        $send['next_func_name'] = $next_func_name;

        $send['next_func_condition'] = '';
        $col_arr = explode(';', $next_func_condition);
        foreach ($col_arr as $col)
        {
            $arr = explode('^', $col);
            if ($send['next_func_condition'] != '')
            {
                $send['next_func_condition'] = $send['next_func_condition'] . ',';
            }
            $send['next_func_condition'] = $send['next_func_condition'] . $arr[0];
        }

        $send['import_func_id'] = $import_func_id;
        $send['import_func_name'] = $import_func_name;
        $send['tip_column'] = $tip_column;

        echo view('Vgrid_aggrid.php', $send);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 通用条件查询模块
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function set_condition($menu_id='')
    {
        $model = new Mcommon();

        $cond_arr = $this->request->getJSON(true);

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
                    if ($opt_1!='in' && $opt_1!='not in')
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
        $dept_cond = $session->get($menu_id.'-dept_cond');
        $dept_fld = $session->get($menu_id.'-dept_fld');
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
    // 更新记录
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function update_row($menu_id='')
    {
        $row_arr = $this->request->getJSON(true);

        // 从session中取出数据
        $session = \Config\Services::session();
        $update_module = $session->get($menu_id.'-update_module');

        if ($update_module == '')
        {
            $this->update_row_1($menu_id, $row_arr);
        }
        else
        {
            $this->update_row_2($menu_id, $row_arr);
        }
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 更新记录,原记录更新
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function update_row_1($menu_id, $arg)
    {
        $row_arr = $arg;

        // 从session中取出数据
        $session = \Config\Services::session();
        $update_table = $session->get($menu_id.'-update_table');
        $primary_key = $session->get($menu_id.'-primary_key');
        $user_workid = $session->get('user_workid');

        $change_fld_str = ''; //变更表项

        $set = '';
        $where = '';
        foreach ($row_arr as $row)
        {
            if ($row['fld_name'] == $primary_key)
            {
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

            // 记录开始日期不拦截
            if ($row['modified'] == false && $row['fld_name'] != '记录开始日期') continue;

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

            // 记录开始日期单独处理
            if ($row['fld_name'] == '记录开始日期')
            {
                $set_str = sprintf('记录结束日期=\'%s\'', $row['value']);
            }

            if ($set != '')
            {
                $set = $set . ',';
                $change_fld_str = $change_fld_str . ',';
            }
            $set = $set . $set_str;
            $change_fld_str = $change_fld_str . $row['fld_name'];
        }

        //增加变更表项、录入人的处理
        $set = sprintf('
            %s,变更表项=\'%s\',录入人=\'%s\'', 
            $set, $change_fld_str, $user_workid);

        $sql = sprintf('update %s set %s where %s', $update_table, $set, $where);

        $model = new Mcommon();

        // 写日志
        $model->sql_log('更新', $menu_id, sprintf('sql=%s',str_replace('"','',$sql)));

        $num = $model->modify($sql);
        exit($num);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 更新记录,原记录不变,增加新记录 
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function update_row_2($menu_id, $arg)
    {
        $row_arr = $arg;

        // 从session中取出数据
        $session = \Config\Services::session();
        $update_table = $session->get($menu_id.'-update_table');
        $primary_key = $session->get($menu_id.'-primary_key');
        $user_workid = $session->get('user_workid');

        $change_fld_str = ''; //变更表项

        $set = '';
        $where = '';
        foreach ($row_arr as $row)
        {
            // 主键处理
            if ($row['fld_name'] == $primary_key)
            {
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

            $set_str = '';

            // 记录开始日期不拦截
            if ($row['modified'] == false && $row['fld_name'] != '记录开始日期') continue;

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

            // 记录开始日期单独处理
            if ($row['fld_name'] == '记录开始日期')
            {
                $set_str = sprintf('记录结束日期=\'%s\'', $row['value']);
            }

            if ($set != '')
            {
                $set = $set . ',';
                $change_fld_str = $change_fld_str . ',';
            }
            $set = $set . $set_str;
            $change_fld_str = $change_fld_str . $row['fld_name'];
        }

        //增加变更表项、录入人、是否有效的处理
        $set = sprintf('
            %s,变更表项=\'%s\',录入人=\'%s\',是否有效=\'0\'', 
            $set, $change_fld_str, $user_workid);

        $model = new Mcommon();

        // 读出原记录
        $read_str = '';
        $insert_str = '';
        $fields = $model->get_fields($update_table);
        foreach ($fields as $field)
        {
            if ($field == $primary_key || $field == '录入时间') continue;

            $fld_str = $field;
            foreach ($row_arr as $row)
            {
                //直接跳出处理
                if ($field == '录入人')
                {
                    $fld_str = sprintf('\'%s\' as 录入人', $user_workid);
                    break;
                }
                else if ($field == '是否有效')
                {
                    $fld_str = sprintf('\'1\' as 是否有效');
                    break;
                }

                if ($row['fld_name'] != $field) continue;

                if ($field == '记录开始日期')
                {
                    $fld_str = sprintf('\'%s\' as %s', $row['value'], $row['fld_name']);
                    break;
                }

                if ($row['modified'] == false) break;

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

        $sql_select = sprintf('select %s from %s where %s', $read_str, $update_table, $where);

        // 插入新记录
        $sql_insert = sprintf('
            insert into %s (%s) %s', 
            $update_table, $insert_str, $sql_select);

        // 更新老记录
        $sql_update = sprintf('update %s set %s where %s', $update_table, $set, $where);

        $model = new Mcommon();

        // 写日志
        $model->sql_log('更新', $menu_id, sprintf('sql=%s',str_replace('"','',$sql_update)));

        $num = $model->exec($sql_insert);
        $num = $model->modify($sql_update);
        exit($num);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 增加新行
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function add_row($menu_id='')
    {
        $row_arr = $this->request->getJSON(true);

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_workid = $session->get('user_workid');
        $update_table = $session->get($menu_id.'-update_table');

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

        $sql = sprintf('insert into %s (%s,录入人,是否有效) values (%s,"%s","1")',
            $update_table, $flds_str, $values_str, $user_workid);

        $model = new Mcommon();

        // 写日志
        $model->sql_log('新增', $menu_id, sprintf('sql=%s',str_replace('"','',$sql)));

        $num = $model->exec($sql);

        exit($num);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 删除记录
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function delete_row($menu_id='')
    {
        $row_arr = $this->request->getJSON(true);

        // 从session中取出数据
        $session = \Config\Services::session();
        $update_table = $session->get($menu_id.'-update_table');
        $primary_key = $session->get($menu_id.'-primary_key');
        $user_workid = $session->get('user_workid');

        $change_fld_str = '删除记录'; //变更表项

        $set = '';
        $where = '';
        foreach ($row_arr as $row)
        {
            //主键
            if ($row['fld_name'] == $primary_key)
            {
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

        $sql_update = sprintf('
            update %s 
            set 变更表项="删除记录",记录结束日期="%s",
                录入人="%s",录入时间="%s",是否有效="0"
            where %s',
            $update_table,date('Y-m-d'),$user_workid,
            date('Y-m-d H:i:s'),$where);

        $model = new Mcommon();
        // 写日志
        $model->sql_log('删除', $menu_id, sprintf('sql=%s',str_replace('"','',$sql_update)));
        // 更新
        $num = $model->modify($sql_update);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 导出到xls
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function export($menu_id='')
    {
        // 从session中取出数据
        $session = \Config\Services::session();
        $query_str = $session->get($menu_id.'-query_str');

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

        // 写日志
        $model->sql_log('导出', $menu_id, $query_str);

        $query = $model->select($query_str);

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
}
