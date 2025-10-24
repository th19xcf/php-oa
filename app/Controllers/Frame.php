<?php
/* v11.16.4.1.202510241050, from office */
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
        $company_id = $session->get('company_id');
        $user_workid = $session->get('user_workid');
        $user_pswd = $session->get('user_pswd');

        $sql = '';
        $sql = sprintf('
            select 
                员工编号,姓名,工号,
                case
                    when t1.角色组!="" and t1.角色编码="" and t2.角色组 is not null then t2.角色编码
                    when t1.角色组!="" and t1.角色编码!="" and t2.角色组 is not null then concat(t2.角色编码,",",t1.角色编码)
                    else t1.角色编码
                end as 角色编码,
                属地赋权,部门编码赋权,部门全称赋权,
                工号限权,调试赋权,维护赋权,
                员工属地,员工部门编码,员工部门全称
            from
            (
                select 
                    员工编号,姓名,
                    工号,角色组,replace(replace(角色编码,"，",",")," ","") as 角色编码,
                    replace(replace(属地赋权,"，",",")," ","") as  属地赋权,
                    replace(replace(部门编码赋权,"，",",")," ","") as 部门编码赋权,
                    replace(replace(部门全称赋权,"，",",")," ","") as 部门全称赋权,
                    工号限权,调试赋权,维护赋权,
                    员工属地,员工部门编码,员工部门全称
                from def_user
                where 有效标识="1" and 员工属地="%s" and 工号="%s"
                group by 员工属地,工号
            ) as t1
            left join
            (
                select 角色组,replace(replace(角色编码,"，",",")," ","") as 角色编码
                from def_role_group
                where 有效标识="1"
            ) as t2 on t1.角色组=t2.角色组', 
            $company_id, $user_workid);

        $model = new Mcommon();
        $query = $model->select($sql);
        $results = $query->getResult();

        foreach ($results as $row)
        {
            // 角色
            $role_arr = explode(',', $row->角色编码);
            $role_arr = array_unique($role_arr);

            $user_role_authz = '';
            foreach ($role_arr as $role)
            {
                $user_role_authz = ($user_role_authz == '') ? sprintf('"%s"', $role) : sprintf('%s,"%s"', $user_role_authz, $role);
            }

            // 个人属地赋权
            if ($row->属地赋权 == '')
            {
                $row->属地赋权 = $company_id;
            }
            $user_location_authz = $row->属地赋权;

            // 部门编码赋权
            $user_dept_code_arr = ($row->部门编码赋权 == '') ? [] : explode(',', $row->部门编码赋权);
            $user_dept_code_arr = array_unique($user_dept_code_arr);

            $user_dept_code_authz = '';
            foreach ($user_dept_code_arr as $dept_code)
            {
                $user_dept_code_authz = ($user_dept_code_authz == '') ? sprintf('"%s"', $dept_code) : sprintf('%s,"%s"', $user_dept_code_authz, $dept_code);
            }

            // 部门全称赋权
            $user_dept_name_arr = ($row->部门全称赋权 == '') ? [] : explode(',', $row->部门全称赋权);
            $user_dept_name_arr = array_unique($user_dept_name_arr);

            $user_dept_name_authz = '';
            foreach ($user_dept_name_arr as $dept_name)
            {
                $user_dept_name_authz = ($user_dept_name_authz == '') ? sprintf('"%s"', $dept_name) : sprintf('%s,"%s"', $user_dept_name_authz, $dept_name);
            }

            // 存入session
            $session_arr = [];
            $session_arr['user_role'] = $row->角色编码;
            $session_arr['user_role_authz'] = $user_role_authz;
            $session_arr['user_location_authz'] = $user_location_authz;
            $session_arr['user_dept_code_authz'] = $user_dept_code_authz;
            $session_arr['user_dept_name_authz'] = $user_dept_name_authz;
            $session_arr['user_workid_authz'] = $row->工号限权;
            $session_arr['user_debug_authz'] = ($user_pswd == $user_workid.$user_workid) ? '1' : $row->调试赋权;
            $session_arr['user_upkeep_authz'] = ($user_pswd == $user_workid.$user_workid) ? '1' : $row->维护赋权;
            $session_arr['user_location'] = $row->员工属地;
            $session_arr['user_dept_code'] = $row->员工部门编码;
            $session_arr['user_dept_name'] = $row->员工部门全称;

            $session = \Config\Services::session();
            $session->set($session_arr);
        }

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_upkeep_authz = $session->get('user_upkeep_authz');
        $user_role_authz = $session->get('user_role_authz');
        $user_location_authz = $session->get('user_location_authz');
        $user_dept_code_authz = $session->get('user_dept_code_authz');
        $user_dept_name_authz = $session->get('user_dept_name_authz');
        $user_workid_authz = $session->get('user_workid_authz');

        $session_arr = [];

        $model = new Mcommon();

        // 读出角色对应的功能赋权
        $sql = sprintf(
            'select 
                t1.角色编码,t1.角色名称,
                t1.功能赋权,
                t1.备注授权,t1.新增授权,t1.修改授权,t1.删除授权,
                t1.维护授权,t1.整表授权,
                t1.导入授权,t1.导出授权,t1.工号限权,
                ifnull(t2.功能编码,"") as 功能编码,
                ifnull(t2.一级菜单,"") as 一级菜单,
                ifnull(t2.二级菜单,"") as 二级菜单,
                ifnull(t2.功能模块,"") as 功能模块,
                ifnull(t2.参数,"") as 参数,
                ifnull(t2.一级菜单顺序,"") as 一级菜单顺序,
                ifnull(t2.二级菜单顺序,"") as 二级菜单顺序,
                ifnull(t2.菜单显示,"") as 菜单显示,
                ifnull(t3.部门编码字段,"") as 部门编码字段,
                ifnull(t3.部门全称字段,"") as 部门全称字段,
                ifnull(t3.属地字段,"") as 属地字段
            from 
            (
                select 角色编码,角色名称,功能赋权,
                    max(备注授权) as 备注授权,
                    max(新增授权) as 新增授权,
                    max(修改授权) as 修改授权,
                    max(删除授权) as 删除授权,
                    max(维护授权) as 维护授权,
                    max(整表授权) as 整表授权,
                    max(导入授权) as 导入授权,
                    max(导出授权) as 导出授权,
                    min(工号限权) as 工号限权
                from view_role
                where 有效标识="1" and 角色编码 in (%s)
                group by 角色编码,功能赋权
            ) as t1
            left join
            (
                select
                    功能编码,
                    ta.一级菜单,ta.二级菜单,
                    功能模块,参数,功能类型,模块名称,
                    ifnull(tb.一级菜单顺序,999) as 一级菜单顺序,
                    二级菜单顺序,
                    菜单显示
                from
                (
                    select 
                        功能编码,一级菜单,二级菜单,
                        功能模块,参数,功能类型,模块名称,
                        菜单顺序 as 二级菜单顺序,菜单显示
                    from def_function
                    where 菜单顺序>0
                ) as ta
                left join
                (
                    select 一级菜单,顺序 as 一级菜单顺序
                    from def_menu_1 
                    where 顺序>0
                ) as tb on ta.一级菜单=tb.一级菜单
                order by 一级菜单顺序,二级菜单顺序
            ) as t2 on t1.功能赋权=t2.功能编码
            left join
            (
                select 查询模块,部门编码字段,部门全称字段,属地字段
                from def_query_config
            ) as t3 on if(t2.功能类型="查询",t2.模块名称,"")=t3.查询模块
            group by t1.功能赋权
            order by t2.一级菜单顺序,t2.二级菜单顺序', $user_role_authz);

        $query = $model->select($sql);
        $results = $query->getResult();

        $json = [];
        $function_authz_arr = [];  // 功能访问权限

        foreach ($results as $row)
        {
            // 功能访问权限
            $function_authz_arr[$row->功能赋权] = $row->功能赋权;

            $session_arr[$row->功能赋权.'-menu_1'] = $row->一级菜单;
            $session_arr[$row->功能赋权.'-menu_2'] = $row->二级菜单;
            $session_arr[$row->功能赋权.'-comment_authz'] = $row->备注授权;
            $session_arr[$row->功能赋权.'-add_authz'] = $row->新增授权;
            $session_arr[$row->功能赋权.'-modify_authz'] = $row->修改授权;
            $session_arr[$row->功能赋权.'-delete_authz'] = $row->删除授权;
            $session_arr[$row->功能赋权.'-upkeep_authz'] = ($user_upkeep_authz == '1') ? $user_upkeep_authz : $row->维护授权;
            $session_arr[$row->功能赋权.'-table_authz'] = $row->整表授权;
            $session_arr[$row->功能赋权.'-import_authz'] = $row->导入授权;
            $session_arr[$row->功能赋权.'-export_authz'] = $row->导出授权;
            $session_arr[$row->功能赋权.'-workid_authz'] = ($user_workid_authz != '0') ? $user_workid_authz : $row->工号限权;
            $session_arr[$row->功能赋权.'-dept_code_fld'] = $row->部门编码字段;
            $session_arr[$row->功能赋权.'-dept_name_fld'] = $row->部门全称字段;
            $session_arr[$row->功能赋权.'-location_fld'] = $row->属地字段;
            $session_arr[$row->功能赋权.'-location_authz'] = '';

            $session_arr[$row->功能赋权.'-dept_code_authz'] = '';
            $session_arr[$row->功能赋权.'-dept_name_authz'] = '';
            $session_arr[$row->功能赋权.'-dept_name_str'] = '';
            $session_arr[$row->功能赋权.'-dept_authz'] = '';
            
            // 显示标志不等于1,不生成菜单
            if ($row->菜单显示 != 1)
            {
                continue;
            }

            // 生成前端页面菜单数据
            $link = '';
            if ($row->参数 != '')
            {
                $link = sprintf("%s/%s/%s?func=%s", $row->功能模块, $row->功能编码, $row->参数, $row->一级菜单);
            }
            else
            {
                $link = sprintf("%s/%s?func=%s", $row->功能模块, $row->功能编码, $row->一级菜单);
            }

            $children = array(
                'text' => sprintf('<a href="javascript:void(0);" tag="%s" onclick="goto(%s)">%s</a>', $link, $row->功能编码, $row->二级菜单),
                'expanded' => true
            );

            $json[$row->一级菜单]['text'] = $row->一级菜单;
            $json[$row->一级菜单]['expanded'] = false;
            $json[$row->一级菜单]['children'][] = $children;
        }

        // 读出角色对应的部门编码赋权
        $sql = sprintf(
            'select 
                t1.GUID,角色编码,功能赋权,编码赋权,
                substring_index(substring_index(编码赋权,",",t2.GUID+1),",",-1) as 部门编码赋权
            from
            (
                select GUID,角色编码,功能赋权,replace(replace(部门编码赋权,"，",",")," ","") as 编码赋权
                from view_role
                where 有效标识="1" and 角色编码 in (%s)
            ) as t1
            inner join def_GUID as t2 on t2.GUID<(length(编码赋权)-length(replace(编码赋权,",",""))+1)
            group by 角色编码,功能赋权,部门编码赋权
            order by 角色编码,功能赋权,部门编码赋权', $user_role_authz);

        $query = $model->select($sql);
        $results = $query->getResult();

        // 角色表中的部门编码赋权
        $func_id = '';
        foreach ($results as $row)
        {
            if ($func_id != $row->功能赋权)
            {
                $func_id = $row->功能赋权;
                $session_arr[$row->功能赋权.'-dept_code_authz']= '';
            }

            if ($session_arr[$row->功能赋权.'-dept_code_fld'] == '')
            {
                continue;
            }

            if ($row->部门编码赋权 != '')
            {
                if ($session_arr[$row->功能赋权.'-dept_code_authz'] == '')
                {
                    $session_arr[$row->功能赋权.'-dept_code_authz'] = sprintf('left(%s,length("%s"))="%s"', $session_arr[$row->功能赋权.'-dept_code_fld'], $row->部门编码赋权, $row->部门编码赋权);
                }
                else
                {
                    $session_arr[$row->功能赋权.'-dept_code_authz'] = sprintf('%s or left(%s,length("%s"))="%s"', $session_arr[$row->功能赋权.'-dept_code_authz'], $session_arr[$row->功能赋权.'-dept_code_fld'], $row->部门编码赋权, $row->部门编码赋权);
                }

                // 数据维护时取出空记录
                if ($session_arr[$row->功能赋权.'-upkeep_authz'] == '1' || $session_arr[$row->功能赋权.'-dept_code_authz'] != '')
                {
                    $session_arr[$row->功能赋权.'-dept_code_authz'] = sprintf('%s or %s=""', $session_arr[$row->功能赋权.'-dept_code_authz'], $session_arr[$row->功能赋权.'-dept_code_fld']);
                }

                continue;
            }

            // 角色表中的部门编码赋权为空,启用用户表中的部门编码赋权
            if ($user_dept_code_authz != '')
            {
                $session_arr[$row->功能赋权.'-dept_code_authz'] = sprintf('instr(%s,%s)', $session_arr[$row->功能赋权.'-dept_code_fld'], $user_dept_code_authz);
            }
        }

        // 角色表中的部门全称赋权
        $sql = sprintf(
            'select 
                t1.GUID,角色编码,功能赋权,全称赋权,
                substring_index(substring_index(全称赋权,",",t2.GUID+1),",",-1) as 部门全称赋权
            from
            (
                select GUID,角色编码,功能赋权,replace(replace(部门全称赋权,"，",",")," ","") as 全称赋权
                FROM view_role
                where 有效标识="1" and 角色编码 in (%s)
            ) as t1
            inner join def_GUID as t2 on t2.GUID<(length(全称赋权)-length(replace(全称赋权,",",""))+1)
            group by 角色编码,功能赋权,部门全称赋权
            order by 角色编码,功能赋权,部门全称赋权', $user_role_authz);

        $query = $model->select($sql);
        $results = $query->getResult();

        foreach ($results as $row)
        {
            if ($session_arr[$row->功能赋权.'-dept_name_fld'] == '')
            {
                continue;
            }

            if ($row->部门全称赋权 != '')
            {
                if ($session_arr[$row->功能赋权.'-dept_name_authz'] == '')
                {
                    $session_arr[$row->功能赋权.'-dept_name_authz'] = sprintf('instr(%s,"%s")', $session_arr[$row->功能赋权.'-dept_name_fld'], $row->部门全称赋权);
                }
                else
                {
                    $session_arr[$row->功能赋权.'-dept_name_authz'] = sprintf('%s or instr(%s,"%s")', $session_arr[$row->功能赋权.'-dept_name_authz'], $session_arr[$row->功能赋权.'-dept_name_fld'], $row->部门全称赋权);
                }

                // 数据维护时取出空记录
                if ($session_arr[$row->功能赋权.'-upkeep_authz'] == '1' || $session_arr[$row->功能赋权.'-dept_name_authz'] != '')
                {
                    $session_arr[$row->功能赋权.'-dept_name_authz'] = sprintf('%s or %s=""', $session_arr[$row->功能赋权.'-dept_name_authz'], $session_arr[$row->功能赋权.'-dept_name_fld']);
                }

                if ($session_arr[$row->功能赋权.'-dept_name_str'] == '')
                {
                    $session_arr[$row->功能赋权.'-dept_name_str'] = sprintf('"%s"', $row->部门全称赋权);
                }
                else
                {
                    $session_arr[$row->功能赋权.'-dept_name_str'] = sprintf('%s,"%s"', $session_arr[$row->功能赋权.'-dept_name_str'], $row->部门全称赋权);
                }

                continue;
            }

            // 角色表中的部门全称赋权为空,启用用户表中的部门全称赋权
            if ($user_dept_name_authz != '')
            {
                $session_arr[$row->功能赋权.'-dept_name_authz'] = sprintf('instr(%s,%s)', $session_arr[$row->功能赋权.'-dept_name_fld'], $user_dept_name_authz);
                $session_arr[$row->功能赋权.'-dept_name_str'] = sprintf('%s', $user_dept_name_authz);
            }
        }

        // 读出角色对应的属地赋权
        $sql = sprintf(
            'select 
                t1.GUID,角色编码,功能赋权,属地,
                substring_index(substring_index(属地,",",t2.GUID+1),",",-1) as 属地赋权
            from
            (
                select GUID,角色编码,功能赋权,replace(replace(属地赋权,"，",",")," ","") as 属地
                from view_role
                where 有效标识="1" and 角色编码 in (%s)
            ) as t1
            inner join def_GUID as t2 on t2.GUID<(length(属地)-length(replace(属地,",",""))+1)
            group by 角色编码,功能赋权,属地赋权
            order by 角色编码,功能赋权,属地赋权', $user_role_authz);

        $query = $model->select($sql);
        $results = $query->getResult();

        foreach ($results as $row)
        {
            if ($session_arr[$row->功能赋权.'-location_fld'] == '')
            {
                continue;
            }

            if ($row->属地赋权 != '')
            {
                if ($session_arr[$row->功能赋权.'-location_authz'] == '')
                {
                    $session_arr[$row->功能赋权.'-location_authz'] = sprintf('instr(%s,"%s")', $session_arr[$row->功能赋权.'-location_fld'], $row->属地赋权);
                }
                else
                {
                    $session_arr[$row->功能赋权.'-location_authz'] = sprintf('%s or instr(%s,"%s")', $session_arr[$row->功能赋权.'-location_authz'], $session_arr[$row->功能赋权.'-location_fld'], $row->属地赋权);
                }

                // 数据维护时取出空记录
                if ($session_arr[$row->功能赋权.'-upkeep_authz'] == '1' || $session_arr[$row->功能赋权.'-location_authz'] != '')
                {
                    $session_arr[$row->功能赋权.'-location_authz'] = sprintf('%s or %s=""', $session_arr[$row->功能赋权.'-location_authz'], $session_arr[$row->功能赋权.'-location_fld']);
                }

                continue;
            }

            // 角色表中的属地赋权为空,启用用户表中的属地赋权
            if ($user_location_authz != '')
            {
                $session_arr[$row->功能赋权.'-location_authz'] = sprintf('locate(%s,"%s")>0', $session_arr[$row->功能赋权.'-location_fld'], $user_location_authz);
            }
        }

        // 条件合并
        foreach ($function_authz_arr as $func_id)
        {
            // 部门编码和部门全称条件合并
            if ($session_arr[$func_id.'-dept_code_authz'] != '' && $session_arr[$func_id.'-dept_name_authz'] != '')
            {
                $session_arr[$func_id.'-dept_authz'] = sprintf('(%s or %s)', $session_arr[$func_id.'-dept_code_authz'], $session_arr[$func_id.'-dept_name_authz']);
            }
            else if ($session_arr[$func_id.'-dept_code_authz'] != '')
            {
                $session_arr[$func_id.'-dept_authz'] = sprintf('(%s)', $session_arr[$func_id.'-dept_code_authz']);
            }
            else if ($session_arr[$func_id.'-dept_name_authz'] != '')
            {
                $session_arr[$func_id.'-dept_authz'] = sprintf('(%s)', $session_arr[$func_id.'-dept_name_authz']);
            }

            if ($session_arr[$func_id.'-location_authz'] != '')
            {
                $session_arr[$func_id.'-location_authz'] = sprintf('(%s)', $session_arr[$func_id.'-location_authz']);
            }

            $session_arr[$func_id.'-dept_authz_cond'] = $session_arr[$func_id.'-dept_authz'];
            $session_arr[$func_id.'-location_authz_cond'] = $session_arr[$func_id.'-location_authz'];
        }

        // 存入session
        $session_arr['function_authz'] = $function_authz_arr;
        $session = \Config\Services::session();
        $session->set($session_arr);

        echo json_encode($json, 320);  //256+64,不转义中文+反斜杠
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 通用初始查询模块
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function init($menu_id='', $front_id='', $front_param='')
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
        $user_location = $session->get('user_location');
        $user_location_authz = $session->get('user_location_authz');
        $comment_authz = $session->get($menu_id.'-comment_authz');
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

        $comment_value_arr = [];  // 前端commet_grid值信息,用于显示
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
        $query_module = ''; //查询模块
        $field_module = ''; //字段模块
        $query_group = '';
        $query_order = '';
        $drill_module = '';  //钻取模块
        $comment_arr = [];  //备注信息
        $comment_module = '';  //备注模块
        $comment_table = '';  //备注表名
        $import_module = '';  //导入模块
        $before_insert = '';  //新增前处理模块
        $after_insert = '';  //新增后处理模块
        $before_update = '';  //更新前处理模块
        $after_update = '';  //更新后处理模块
        $data_upkeep = '';  //数据整理模块
        $chart_func_id = '';  //图形模块
        $result_count = '';
        $data_table = '';
        $data_model = '';

        $drill_arr = [];  // 钻取模块参数

        $sql = sprintf('
            select 
                查询模块,模块类型,字段模块,
                库名,查询表名,数据表名,数据模式,
                主键字段,部门编码字段,部门全称字段,
                工号字段,属地字段,
                查询条件,汇总条件,排序条件,初始条数,
                新增前处理模块,新增后处理模块,
                更新前处理模块,更新后处理模块,
                数据整理模块,
                钻取模块,
                备注模块,
                t1.导入模块,ifnull(t2.标签名称,"") as 标签名称,
                图形模块,表样式
            from def_query_config as t1
            left join
            (
                select 导入模块,标签名称
                from def_import_config
            ) as t2 on t1.导入模块=t2.导入模块
            where 查询模块 in 
                (
                    select 模块名称 
                    from def_function
                    where 有效标识="1" and 功能编码="%s"
                )', $menu_id);

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

            $query_module = $row->查询模块;
            $field_module = $row->字段模块;
            $query_group = $row->汇总条件;
            $query_order = $row->排序条件;

            $drill_module = $row->钻取模块;
            $comment_module = $row->备注模块;

            $import_module = $row->导入模块;
            $import_tag_name = $row->标签名称;

            $before_insert = $row->新增前处理模块;
            $after_insert = $row->新增后处理模块;
            $before_update = $row->更新前处理模块;
            $after_update = $row->更新后处理模块;
            $data_upkeep = $row->数据整理模块;
            $grid_style = ($row->表样式 == '') ? '表样式_A' : $row->表样式;

            $chart_func_id = $row->图形模块;

            $data_table = $row->数据表名;
            $data_model = $row->数据模式;

            break;
        }

        $tb_arr = [];  // 控制菜单栏
        $tb_arr['备注授权'] = ($comment_authz=='1' and $comment_module!='') ? true : false;
        $tb_arr['钻取授权'] = ($drill_module!='') ? true : false;
        $tb_arr['修改授权'] = ($modify_authz=='1') ? true : false ;
        $tb_arr['删除授权'] = ($delete_authz=='1') ? true : false ;
        $tb_arr['新增授权'] = ($add_authz=='1') ? true : false ;
        $tb_arr['整表授权'] = ($table_authz=='1') ? true : false ;
        $tb_arr['导入授权'] = ($import_authz=='1' && $import_module!='') ? true : false ;
        $tb_arr['导出授权'] = ($export_authz=='1') ? true : false ;
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

        //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
        // 取出备注模块对应的表配置
        //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
        $sql = sprintf('
            select 
                t1.备注模块,t1.备注表名,t1.功能编码,t1.原表字段,
                ifnull(t2.模块名称,"") as 模块名称,
                ifnull(t3.数据表名,"") as 数据表名
            from def_comment_config as t1
            left join def_function as t2 on t1.功能编码=t2.功能编码
            left join def_query_config as t3 on t2.模块名称=t3.查询模块
            where t1.备注模块="%s"', $comment_module);

        $results = $model->select($sql)->getResult();
        foreach ($results as $row)
        {
            $comment_table = $row->数据表名;

            str_replace(' ', '', $row->原表字段);
            str_replace('；', ';', $row->原表字段);

            $comment_arr['模块名称'] = $comment_module;
            $comment_arr['功能编码'] = $row->功能编码;
            $comment_arr['备注表名'] = $row->数据表名;
            $comment_arr['原表字段'] = $row->原表字段;

            break;  // 只取第一个
        }

        //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+= 
        // 处理前端备注数据
        //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+= 
        $front_arr = [];
        $front_where = '';
        $disp_col_arr = [];  // 显示列
        $color_arr = [];  // 颜色标注
        $color_col_num = 0;

        if ($front_id == '查看说明')
        {
            $front_arr = json_decode($front_param);
            $front_where = '';

            foreach ($front_arr as $item)
            {
                switch ($item->列类型)
                {
                    case '数值':
                        $front_where = ($front_where == '') ? sprintf('%s=%s', $item->字段名, $item->取值) : $front_where . sprintf(' and %s=%s', $item->字段名, $item->取值);
                        break;
                    case '字符':
                    case '日期':
                        $front_where = ($front_where == '') ? sprintf('%s="%s"', $item->字段名, $item->取值) : $front_where . sprintf(' and %s="%s"', $item->字段名, $item->取值);
                        break;
                }
            }
        }

        //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
        // 取出钻取模块对应的表配置
        //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
        $sql = sprintf('
            select 
                钻取模块,页面选项,t1.功能编码,钻取字段,钻取条件,
                if(t2.二级菜单 is null,"",if(t1.标签副名称="",t2.二级菜单,concat(t2.二级菜单,"-",t1.标签副名称))) as 标签名称
            from def_drill_config as t1
            left join def_function as t2 on t1.功能编码=t2.功能编码
            where 钻取模块="%s"
            order by 顺序,convert(页面选项 using gbk)', $drill_module);

        $results = $model->select($sql)->getResult();
        foreach ($results as $row)
        {
            if (array_key_exists($row->功能编码,$function_authz) == false)
            {
                continue;
            }

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
        if ($front_id == '数据钻取')
        {
            $front_arr = json_decode($front_param);
            $front_where = str_replace('`', '"', $front_arr->钻取条件);
            $disp_col_arr = $front_arr->字段选择;

            foreach ($front_arr as $key => $value)
            {
                if ($key == '字段选择')
                {
                    continue;
                }
                if ($key == '颜色标注')
                {
                    $color_arr = $value;

                    if ($value->col_name_1 != '' && $value->col_name_2 != '')
                    {
                        // 存入session
                        $session_arr['color_mark'] = $color_arr;
                        $session = \Config\Services::session();
                        $session->set($session_arr);
                    }

                    continue;
                }

                if ($key == '钻取字段' || $key == '钻取条件') continue;
                $front_where = str_replace(sprintf('$%s',$key), $value, $front_where);
            }
        }

        //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+= 
        // 处理颜色标注
        //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+= 
        $session = \Config\Services::session();
        if ($session->get('color_mark') != null)
        {
            $color_arr = $session->get('color_mark');
        }

        //+=+=+=+=+=+=+=+=+=+=+=+= 
        // 处理列的相关数据
        //+=+=+=+=+=+=+=+=+=+=+=+= 
        // 前端data_grid列信息,手工增加序号列
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
            select 功能编码,字段模块,部门编码字段,部门全称字段,
                工号字段,属地字段,
                列名,列类型,列宽度,字段名,查询名,
                赋值类型,对象,对象名称,对象表名,缺省值,主键,
                工号限权,可筛选,可汇总,可新增,可修改,不可为空,可颜色标注,
                提示条件,提示样式设置,异常条件,异常样式设置,字符转换,
                加密显示,列顺序
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
            $arr['列类型'] = $row->列类型;
            $arr['字段名'] = $row->字段名;
            $arr['查询名'] = $row->查询名;
            $arr['主键'] = $row->主键;
            $arr['赋值类型'] = $row->赋值类型;
            $arr['对象'] = $row->对象;
            $arr['对象名称'] = $row->对象名称;
            $arr['对象表名'] = $row->对象表名;
            $arr['工号字段'] = $row->工号字段;
            $arr['工号限权'] = $row->工号限权;
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
            $arr['加密显示'] = $row->加密显示;
            $arr['表外字段'] = '0';

            array_push($columns_arr, $arr);

            $arr['查询名'] = '';
            $arr['提示条件'] = ($row->提示条件 == '') ? '' : '1';
            $arr['异常条件'] = ($row->异常条件 == '') ? '' : '1';
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
            $value_arr['赋值类型'] = $row->赋值类型;
            $value_arr['对象名称'] = $row->对象;
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

                $obj_sql = '';
                if ($user_location_authz == '')
                {
                    $obj_sql = sprintf('
                        select 对象名称,对象值,if(对象显示值="",对象值,对象显示值) as 对象显示值,
                            上级对象名称,上级对象值,if(上级对象显示值="",上级对象值,上级对象显示值) as 上级对象显示值
                        from def_object 
                        where 有效标识="1"
                            and 对象名称="%s"
                        order by convert(对象值 using gbk)',
                        $row->对象);
                }
                else
                {
                    $obj_sql = sprintf('
                        select 对象名称,对象值,if(对象显示值="",对象值,对象显示值) as 对象显示值,
                            上级对象名称,上级对象值,if(上级对象显示值="",上级对象值,上级对象显示值) as 上级对象显示值
                        from def_object 
                        where 有效标识="1"
                            and 对象名称="%s"
                            and (属地="" or locate(属地,"%s")>0)
                        order by convert(对象值 using gbk)',
                        $row->对象, $user_location_authz);
                }

                $qry = $model->select($obj_sql);
                $rslt = $qry->getResult();

                foreach($rslt as $vv)
                {
                    if (strpos(strtolower($vv->对象值),'select') !== false)
                    {
                        $r = $model->select($vv->对象值)->getResultArray();
                        foreach($r as $v)
                        {
                            $object_arr[$vv->对象名称]['上级对象名称'] = $vv->上级对象名称;
                            if (array_key_exists($vv->上级对象值, $object_arr[$row->对象]) == false)
                            {
                                $object_arr[$row->对象][$vv->上级对象值] = [];
                                $object_arr[$row->对象][$vv->上级对象值]['对象值'] = [];
                                $object_arr[$row->对象][$vv->上级对象值]['对象显示值'] = [];
                            }

                            array_push($object_arr[$row->对象][$vv->上级对象值]['对象值'], $v[$row->对象]);
                            array_push($object_arr[$row->对象][$vv->上级对象值]['对象显示值'], $v[$row->对象]);
                        }
                    }
                    else
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
        }

        if (count($comment_value_arr) > 0)
        {
            array_push($comment_value_arr, array('列名'=>'备注模块','字段名'=>'备注模块','列类型'=>'字符','取值'=>$comment_module));
            array_push($comment_value_arr, array('列名'=>'备注说明','字段名'=>'备注说明','列类型'=>'字符','取值'=>''));
        }

        // 拼出查询语句
        $worning_arr = [];  //警告
        $error_arr = [];    //异常
        $worning_str = '';  //警告
        $error_str = '';    //异常

        $select_str = '';
        $send_str = '';
        foreach ($columns_arr as $column) 
        {
            if ($column['提示条件'] != '')
            {
                array_push($worning_arr, array('列名'=>sprintf('提示^%s',$column['列名'])));

                if ($worning_str == '')
                {
                    $worning_str = sprintf('if(%s,"1","0") as `提示^%s`', $column['提示条件'], $column['列名']);
                }
                else
                {
                    $worning_str = sprintf('%s,if(%s,"1","0") as `提示^%s`', $worning_str, $column['提示条件'], $column['列名']);
                }
            }

            if ($column['异常条件'] != '')
            {
                array_push($error_arr, array('列名'=>sprintf('异常^%s',$column['列名']), '表外字段'=>'1'));

                if ($error_str == '')
                {
                    $error_str = sprintf('if(%s,"1","0") as `异常^%s`', $column['异常条件'], $column['列名']);
                }
                else
                {
                    $error_str = sprintf('%s,if(%s,"1","0") as `异常^%s`', $error_str, $column['异常条件'], $column['列名']);
                }
            }

            if ($select_str != '')
            {
                $select_str = $select_str . ',';
                $send_str = $send_str . ',';
            }

            if ($column['字符转换'] == '1')
            {
                $select_str = sprintf('%s replace(replace(%s,"\"","~~"),"\'","~~") as `%s`', $select_str, $column['查询名'], $column['列名']);
            }
            else if ($column['加密显示'] == '1')
            {
                $select_str = sprintf('%s "*" as `%s`', $select_str, $column['查询名'], $column['列名']);
            }
            else if ($column['工号限权'] != '0')
            {
                $select_str = sprintf('%s if(%s="%s",%s,"*") as `%s`', $select_str, $column['工号字段'], $user_workid, $column['查询名'], $column['列名']);
            }
            else
            {
                $select_str = sprintf('%s %s as `%s`', $select_str, $column['查询名'], $column['列名']);
            }

            $send_str = sprintf('%s %s as `%s`', $send_str, $column['查询名'], $column['列名']);
        }

        if ($worning_str != '')
        {
            $columns_arr = array_merge($columns_arr, $worning_arr);
            $send_columns_arr = array_merge($send_columns_arr, $worning_arr);

            $select_str = sprintf('%s,%s', $select_str, $worning_str);
            $send_str = sprintf('%s,%s', $send_str, $worning_str);
        }

        if ($error_str != '')
        {
            $columns_arr = array_merge($columns_arr, $error_arr);
            $send_columns_arr = array_merge($send_columns_arr, $error_arr);

            $select_str = sprintf('%s,%s', $select_str, $error_str);
            $send_str = sprintf('%s,%s', $send_str, $error_str);
        }

        $query_sql = sprintf('select (@i:=@i+1) as 序号,%s 
            from %s,(select @i:=0) as xh', 
            $select_str, $query_table);

        $send_sql = sprintf('select (@i:=@i+1) as 序号,%s 
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

        // CI取出的都是字符型,数值型做强制转换
        for ($i=0; $i<count($send_results); $i++)
        {
            $send_results[$i]->序号 = (int) $send_results[$i]->序号;
            foreach ($columns_arr as $column)
            {
                if (array_key_exists('列类型', $column) == false) continue;
                if ($column['列类型'] == '数值' && (strpos($send_results[$i]->{$column['列名']},'.') === false))
                {
                    $send_results[$i]->{$column['列名']} = (int)$send_results[$i]->{$column['列名']};
                }
                else if ($column['列类型'] == '数值' && (strpos($send_results[$i]->{$column['列名']},'.') !== false))
                {
                    $send_results[$i]->{$column['列名']} = (float)$send_results[$i]->{$column['列名']};
                }
            }
        }

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
        $session_arr[$menu_id.'-front_where'] = '';
        $session_arr[$menu_id.'-front_group'] = '';
        $session_arr[$menu_id.'-sp_name'] = $sp_name;
        $session_arr[$menu_id.'-sp_str'] = $sp_sql;

        $session_arr[$menu_id.'-import_module'] = $import_module;
        $session_arr[$menu_id.'-import_tag_name'] = $import_tag_name;

        $session_arr[$menu_id.'-before_insert'] = $before_insert;
        $session_arr[$menu_id.'-after_insert'] = $after_insert;
        $session_arr[$menu_id.'-after_update'] = $after_update;
        $session_arr[$menu_id.'-before_update'] = $before_update;
        $session_arr[$menu_id.'-data_upkeep'] = $data_upkeep;
        $session_arr[$menu_id.'-grid_style'] = $grid_style;

        $session_arr[$menu_id.'-data_table'] = $data_table;
        $session_arr[$menu_id.'-data_model'] = $data_model;

        $session_arr[$menu_id.'-comment_arr'] = $comment_arr;
        $session_arr[$menu_id.'-comment_table'] = $comment_table;
        $session_arr[$menu_id.'-comment_module'] = $comment_module;

        // chart session初始化
        $session_arr[$menu_id.'-chart_drill_arr'] = [];
        $session_arr[sprintf('%s^%s-chart_drill_cond_str',$menu_id,$chart_func_id)] = '';
        $session_arr[sprintf('%s^%s-chart_drill_title_str',$menu_id,$chart_func_id)] = '';

        $session = \Config\Services::session();
        $session->set($session_arr);

        // 生成前端图形数据
        $chart_arr = $this->get_chart_data($menu_id, $chart_func_id, '', '');

        //返回页面
        $send = [];
        $send['grid_style'] = json_encode($grid_style);
        $send['drill_module'] = json_encode(($user_debug_authz=='1') ? $drill_module : '');
        $send['upkeep_module'] = json_encode(($user_debug_authz=='1') ? $data_upkeep : '');
        $send['import_module'] = json_encode(($user_debug_authz=='1') ? $import_module : '');
        $send['SQL'] = json_encode(($user_debug_authz=='1') ? $send_sql : '');
        $send['menu_json'] = json_encode($menu_arr);
        $send['toolbar_json'] = json_encode($tb_arr);
        $send['columns_json'] = json_encode($send_columns_arr);
        $send['data_col_json'] = json_encode($data_col_arr);
        $send['data_value_json'] = json_encode($send_results);
        $send['comment_value_json'] = json_encode($comment_value_arr);
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
        $send['query_module'] = $query_module;
        $send['field_module'] = $field_module;
        $send['data_model'] = $data_model;
        $send['primary_key'] = $primary_key;
        $send['back_where'] = strtr($where, '"', '');
        $send['back_group'] = $group;

        $send['comment_json'] = json_encode($comment_arr);

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
        $user_debug_authz = $session->get('user_debug_authz');
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

        $sql = sprintf('select (@i:=@i+1) as 序号,%s from %s,(select @i:=0) as xh', 
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

        // 存入session
        $session_arr = [];
        $session_arr[$menu_id.'-front_where'] = $front_where;
        $session_arr[$menu_id.'-front_group'] = $front_group;
        $session = \Config\Services::session();
        $session->set($session_arr);

        // 写日志
        $model->sql_log('条件查询',$menu_id,sprintf('表=%s,条件=%s', $query_table, str_replace('"','',$where)));

        // 读出数据
        $query = $model->select($sql);
        $results = $query->getResult();

        //返回页面
        $send = [];
        $send['results'] = $results;

        $sql = str_replace('\'','~~',$sql);
        $sql = str_replace('"','~~',$sql);
        $send['SQL'] = ($user_debug_authz=='1') ? $sql : '';

        exit(json_encode($send));
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
        $user_debug_authz = $session->get('user_debug_authz');
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

        //返回页面
        $send = [];
        $send['results'] = $results;

        $sp_sql = str_replace('\'','~~',$sp_sql);
        $sp_sql = str_replace('"','~~',$sp_sql);
        $send['SQL'] = ($user_debug_authz=='1') ? $sp_sql : '';

        exit(json_encode($send));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 新增说明记录
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function comment_add($menu_id='')
    {
        $request = \Config\Services::request();
        $arg = $request->getJSON(true);

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_workid = $session->get('user_workid');
        $comment_table = $session->get($menu_id.'-comment_table');
        $comment_module = $session->get($menu_id.'-comment_module');

        $model = new Mcommon();

        $fld_str = '';
        $value_str = '';
        foreach ($arg as $item)
        {
            switch ($item['fld_type'])
            {
                case '数值':
                    $fld_str = ($fld_str == '') ? $item['fld_name'] : $fld_str . ',' . $item['fld_name'];
                    $value_str = ($value_str == '') ? $item['value'] : $value_str . ',' . $item['value'];
                    break;
                case '字符':
                case '日期':
                    $fld_str = ($fld_str == '') ? $item['fld_name'] : $fld_str . ',' . $item['fld_name'];
                    $value_str = ($value_str == '') ? sprintf('"%s"', $item['value']) : $value_str . ',' . sprintf('"%s"', $item['value']);
                    break;
            }
        }

        $sql = sprintf('
            insert into %s (%s,操作人员) values (%s,"%s")',
            $comment_table, $fld_str, $value_str, $user_workid);

        $num = $model->exec($sql);
        exit(sprintf('`添加说明`成功,添加 %d 条记录',$num));
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
                        if (array_key_exists('fld_name', $row) == false) continue;

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
                        if (array_key_exists('fld_name', $row) == false) continue;

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
    public function export($menu_id='', $arg='')
    {
        $filter_arr = json_decode($arg);

        // 取出过滤条件
        $filter_cond = '';
        foreach ($filter_arr as $key => $value_arr)
        {
            $filter_str = '';
            if (property_exists($value_arr, 'operator'))
            {
                $cond_1 = $this->get_filter($key, $value_arr->condition1);
                $cond_2 = $this->get_filter($key, $value_arr->condition2);
                $filter_str = sprintf('(%s %s %s)', $cond_1, $value_arr->operator, $cond_2);
            }
            else
            {
                $filter_str = $this->get_filter($key, $value_arr);
            }

            if ($filter_cond == '')
            {
                $filter_cond = $filter_str;
            }
            else
            {
                $filter_cond = $filter_cond . ' and ' . $filter_str;
            }
        }

        // 从session中取出数据
        $session = \Config\Services::session();
        $query_str = $session->get($menu_id.'-query_str');
        $sp_name = $session->get($menu_id.'-sp_name');
        $sp_str = $session->get($menu_id.'-sp_str');
        $query_table = $session->get($menu_id.'-query_table');
        $select_str = $session->get($menu_id.'-select_str');
        $back_where = $session->get($menu_id.'-back_where');
        $back_group = $session->get($menu_id.'-back_group');
        $order = $session->get($menu_id.'-back_order');
        $front_where = $session->get($menu_id.'-front_where');
        $front_group = $session->get($menu_id.'-front_group');

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
            // 拼出查询条件
            $where = ($back_where == '') ? '' : $back_where;
            if ($where == '')
            {
                $where = ($front_where == '') ? '' : $front_where;
            }
            else
            {
                $where = ($front_where == '') ? $where : $where . ' and ' . $front_where;
            }
            if ($where == '')
            {
                $where = ($filter_cond == '') ? '' : $filter_cond;
            }
            else
            {
                $where = ($filter_cond == '') ? $where : $where . ' and ' . $filter_cond;
            }

            // 拼出group by
            $group = ($back_group == '') ? '' : $back_group;
            if ($group == '')
            {
                $group = ($front_group == '') ? '' : $front_group;
            }
            else
            {
                $group = ($front_group == '') ? $group : $group . ', ' . $front_group;
            }

            // 拼出查询语句
            $query_str = sprintf('select %s from %s', $select_str, $query_table);
            $query_str = ($where == '') ? $query_str : $query_str . ' where ' . $where;
            $query_str = ($group == '') ? $query_str : $query_str . ' group by ' . $group;
            $query_str = ($order == '') ? $query_str : $query_str . ' order by ' . $order;

            // 写日志
            $model->sql_log('导出', $menu_id, str_replace("'",'',str_replace('"','',$query_str)));
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
            $request = \Config\Services::request();
            $new_pswd = $request->getPost('pswd_1');

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
            if (array_key_exists('fld_name', $row) == false) continue;
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
            if (array_key_exists('赋值类型', $column) == false) continue;
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

        $option = explode('^', $drill_arr[1]['钻取选项'])[0];
        $chart_id = explode('^', $drill_arr[1]['钻取选项'])[1];
        $drill_id = explode('^', $drill_arr[1]['钻取选项'])[2];

        $model = new Mcommon();

        $sql = sprintf('
            select 钻取模块,钻取选项,钻取字段,钻取条件,图形模块
            from def_chart_drill_config
            where 顺序>0 and 钻取选项="%s" and 图形模块="%s" and 钻取模块="%s"',
            $option, $chart_id, $drill_id);

        $results = $model->select($sql)->getResult();

        foreach ($results as $row)
        {
            if ($row->钻取字段 == '') continue;

            $col_arr = explode(';', $row->钻取字段);
            for ($ii=0; $ii<count($col_arr); $ii++)
            {
                if (array_key_exists('钻取参数', $drill_arr[2]) == false)
                {
                    $drill_arr[2]['钻取参数'] = '';
                    continue;
                }
                $drill_arr[2]['钻取参数'] = sprintf('%s;%s^%s', $drill_arr[2]['钻取参数'], $col_arr[$ii], $drill_arr[2][$col_arr[$ii]]);
            }
        }

        $chart_arr = $this->get_chart_data($menu_id, $chart_id, '', $drill_arr[2]['钻取参数']);
        exit(json_encode($chart_arr));

        // 从session中取出数据
        $session = \Config\Services::session();
        $chart_drill_arr = $session->get($menu_id.'-chart_drill_arr');

        // 生成前端图形数据
        $arr = explode('^', $drill_arr[2]['SID']);

        $chart_arr = [];
        if ($chart_drill_arr == [])
        {
            $chart_arr = $this->get_chart_data($menu_id, $arr[0], $arr[1], '');
        }
        else
        {
            $chart_arr = $chart_drill_arr;
        }

        $sql = sprintf('
            select t1.图形模块,图形编号,图形名称,图形类型,
                取数方式,查询表名,查询字段,查询条件,汇总条件,排序条件,
                字段模块,页面布局,钻取模块,条件叠加,顺序,
                钻取字段,钻取条件
            from
            (
                select 图形模块,图形编号,图形名称,图形类型,
                    取数方式,查询表名,查询字段,属地字段,查询条件,汇总条件,排序条件,
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

        $results = $model->select($sql)->getResult();
        foreach ($results as $row)
        {
            $drill_where = array('条件'=>'', '副标题'=>'');
            if ($row->钻取条件 != '')
            {
                $col_arr = explode(';', $row->钻取字段);
                $drill_where['条件'] = str_replace('`', '"', $row->钻取条件);
    
                foreach ($drill_arr[2] as $key => $value)
                {
                    $drill_where['条件'] = str_replace(sprintf('$%s',$key), $value, $drill_where['条件']);
    
                    if(strpos($row->钻取字段, $key) !== false)
                    {
                        $drill_where['副标题'] = ($drill_where['副标题']=='') ? $value : $drill_where['副标题'].','.$value;
                    }
                }
            }

            // 生成钻取图形数据
            $arr = $this->get_chart_data($menu_id, $row->图形模块, $row->图形编号, $drill_where);
            foreach ($arr as $key => $value)
            {
                if (array_key_exists($key, $chart_arr) == false)
                {
                    $chart_arr[$key] = [];
                }
                $chart_arr[$key][$row->图形编号] = $value[$row->图形编号];
            }

            // 存入session
            $session_arr = [];
            $session_arr[$menu_id.'-chart_drill_arr'] = $chart_arr;
            $session = \Config\Services::session();
            $session->set($session_arr);
        }

        exit(json_encode($chart_arr));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 取出前端图形数据
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function get_chart_data($menu_id, $chart_id, $chart_code='', $front_where='')
    {
        $model = new Mcommon();

        // 从session中取出数据
        $session = \Config\Services::session();
        $location_authz_cond = $session->get($menu_id.'-location_authz_cond');
        $dept_authz_cond = $session->get($menu_id.'-dept_authz_cond');
        $dept_name_str = $session->get($menu_id.'-dept_name_str');
        $query_table = $session->get($menu_id.'-query_table');
        $chart_drill_cond_str = $session->get(sprintf('%s^%s-chart_drill_cond_str',$menu_id,$chart_id));
        $chart_drill_title_str = $session->get(sprintf('%s^%s-chart_drill_title_str',$menu_id,$chart_id));

        //$drill_where = ($front_where == []) ? '' : $front_where['条件'];
        //$drill_title = ($front_where == []) ? '' : $front_where['副标题'];
        $drill_where = '';
        $drill_title = '';

        $chart_data = [];
        $chart_arr = [];

        if ($chart_code == '')  // 图形初始
        {
            $sql = sprintf('
                select 图形模块,图形编号,图形名称,图形类型,
                    取数方式,查询表名,查询字段,属地字段,查询条件,汇总条件,排序条件,记录条数,
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
                    取数方式,查询表名,查询字段,属地字段,查询条件,汇总条件,排序条件,记录条数,
                    字段模块,页面布局,钻取模块,条件叠加,顺序
                from def_chart_config
                where 有效标识="1" and 图形模块="%s" and 图形编号="%s" and 顺序>0
                order by 图形模块,图形编号,顺序', 
                $chart_id, $chart_code);
        }

        $results = $model->select($sql)->getResult();

        foreach ($results as $row)
        {
            $arr = [];

            if ($row->取数方式 == '存储过程')
            {
                // 替换参数
                $row->查询表名 = str_replace('$查询表名', sprintf('%s',$query_table), $row->查询表名);
                $row->查询表名 = str_replace('$[部门全称赋权]', sprintf('\'[%s]\'',$dept_name_str), $row->查询表名);
                $data_sql = sprintf('call %s', $row->查询表名);

                if ($front_where != '')
                {
                    $params = explode(';', $front_where);
                    for ($ii=0; $ii<count($params); $ii++)
                    {
                        $arg = explode('^', $params[$ii]);
                        $data_sql = str_replace(sprintf('$%s',$arg[0]), $arg[1], $data_sql);
                    }
                }

                $sp_data = $model->select($data_sql)->getResult();

                if ($sp_data == [])
                {
                    continue;
                }

                foreach ($sp_data as $key => $value)
                {
                    if (array_key_exists($value->图形模块, $arr) == false)
                    {
                        $arr[$value->图形模块] = [];
                        $arr[$value->图形模块]['图形模块'] = $value->图形模块;
                        $arr[$value->图形模块]['图形编号'] = $value->图形编号;
                        $arr[$value->图形模块]['取数方式'] = $row->取数方式;
                        $arr[$value->图形模块]['SQL'] = $data_sql;
                        $arr[$value->图形模块]['图形名称'] = $value->图形名称;
                        $arr[$value->图形模块]['图形类型'] = $row->图形类型;
                        $arr[$value->图形模块]['页面布局'] = $value->页面布局;
                        $arr[$value->图形模块]['字段模块'] = $row->字段模块;
                        $arr[$value->图形模块]['钻取模块'] = $row->钻取模块;
                        $arr[$value->图形模块]['数据'] = [];
                    }
                    array_push($arr[$value->图形模块]['数据'], $value);
                }

                foreach ($arr as $key => $value)
                {
                    $chart = $this->get_chart_data_single($menu_id, $value);
                    foreach ($chart as $key => $value)
                    {
                        if (array_key_exists($key, $chart_arr) == false)
                        {
                            $chart_arr[$key] = [];
                        }
                        $chart_arr[$key] = $value;
                    }
                }
            }

            else
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
                if($row->属地字段 != '' && $location_authz_cond != '')
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
                        $chart_drill_title_str = sprintf('%s,%s', $chart_drill_title_str, $drill_title);
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
                $arr = [];
                if (array_key_exists($row->图形模块, $arr) == false)
                {
                    $arr[$row->图形模块] = [];
                }

                $arr[$row->图形模块]['图形模块'] = $row->图形模块;
                $arr[$row->图形模块]['图形编号'] = $row->图形编号;
                $arr[$row->图形模块]['取数方式'] = $row->取数方式;
                $arr[$row->图形模块]['SQL'] = $data_sql;
                $arr[$row->图形模块]['图形名称'] = ($chart_drill_title_str == '') ? $row->图形名称 : sprintf('%s(%s)', $row->图形名称, $chart_drill_title_str);
                $arr[$row->图形模块]['图形类型'] = $row->图形类型;
                $arr[$row->图形模块]['页面布局'] = $row->页面布局;
                $arr[$row->图形模块]['字段数'] = 0;
                $arr[$row->图形模块]['字段模块'] = $row->字段模块;
                $arr[$row->图形模块]['字段'] = [];
                $arr[$row->图形模块]['钻取模块'] = $row->钻取模块;
                $arr[$row->图形模块]['数据'] = $chart_data;

                $chart = $this->get_chart_data_single($menu_id, $arr[$row->图形模块]);
                foreach ($chart as $key => $value)
                {
                    if (array_key_exists($row->图形模块, $chart_arr) == false)
                    {
                        $chart_arr[$row->图形模块] = [];
                    }
                    if (array_key_exists($row->图形编号, $chart_arr[$row->图形模块]) == false)
                    {
                        $chart_arr[$row->图形模块][$row->图形编号] = [];
                    }

                    $chart_arr[$row->图形模块][$row->图形编号] = $value[$row->图形编号];
                }
            }
        }

        return $chart_arr;
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 取出前端图形数据
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function get_chart_data_single($menu_id, $data)
    {
        $chart_id = $data['图形模块'];
        $chart_code = $data['图形编号'];

        // 从session中取出数据
        $session = \Config\Services::session();
        $user_debug_authz = $session->get('user_debug_authz');
        $chart_drill_cond_str = $session->get(sprintf('%s^%s-chart_drill_cond_str',$menu_id,$chart_id));
        $chart_drill_title_str = $session->get(sprintf('%s^%s-chart_drill_title_str',$menu_id,$chart_id));

        $chart_arr = [];
        $chart_arr[$chart_id] = [];
        $chart_arr[$chart_id][$chart_code] = [];

        $chart_arr[$chart_id][$chart_code]['图形模块'] = $data['图形模块'];
        $chart_arr[$chart_id][$chart_code]['图形编号'] = $data['图形编号'];
        $chart_arr[$chart_id][$chart_code]['取数方式'] = $data['取数方式'];
        $chart_arr[$chart_id][$chart_code]['图形名称'] = $data['图形名称'];
        $chart_arr[$chart_id][$chart_code]['图形类型'] = $data['图形类型'];
        $chart_arr[$chart_id][$chart_code]['字段模块'] = $data['字段模块'];
        $chart_arr[$chart_id][$chart_code]['页面布局'] = $data['页面布局'];
        $chart_arr[$chart_id][$chart_code]['字段'] = [];
        $chart_arr[$chart_id][$chart_code]['字段数'] = 0;
        $chart_arr[$chart_id][$chart_code]['数据'] = $data['数据'];
        $chart_arr[$chart_id][$chart_code]['钻取模块'] = [];

        $data_sql = str_replace('\'','~~',$data['SQL']);
        $data_sql = str_replace('"','~~',$data_sql);
        $chart_arr[$chart_id][$chart_code]['SQL'] = ($user_debug_authz=='1') ? $data_sql : '';

        $model = new Mcommon();

        // 图形列信息
        $col_sql = sprintf('
            select 字段模块,列名,字段名,坐标轴,图形类型
            from def_chart_column
            where 字段模块="%s" and 顺序>0
            order by 字段模块,顺序', $data['字段模块']);

        $col_results = $model->select($col_sql)->getResult();
        foreach ($col_results as $col_row)
        {
            if (array_key_exists($col_row->字段名, $chart_arr[$chart_id][$chart_code]['字段']) == false)
            {
                $chart_arr[$chart_id][$chart_code]['字段'][$col_row->字段名] = [];
            }
            $chart_arr[$chart_id][$chart_code]['字段数'] += 1;
            $chart_arr[$chart_id][$chart_code]['字段'][$col_row->字段名]['列名'] = $col_row->列名;
            $chart_arr[$chart_id][$chart_code]['字段'][$col_row->字段名]['字段名'] = $col_row->字段名;
            $chart_arr[$chart_id][$chart_code]['字段'][$col_row->字段名]['坐标轴'] = $col_row->坐标轴;
            $chart_arr[$chart_id][$chart_code]['字段'][$col_row->字段名]['图形类型'] = $col_row->图形类型;
        }

        // 钻取模块
        $drill_sql = sprintf('
            select 钻取模块,钻取选项,replace(钻取字段,"；",";") as 钻取字段,钻取条件,图形模块
            from def_chart_drill_config
            where 钻取模块="%s" and 顺序>0
            order by 钻取模块,顺序', $data['钻取模块']);

        $drill_results = $model->select($drill_sql)->getResult();
        foreach ($drill_results as $drill_row)
        {
            if (array_key_exists($drill_row->钻取选项, $chart_arr[$chart_id][$chart_code]['钻取模块']) == false)
            {
                $chart_arr[$chart_id][$chart_code]['钻取模块'][$drill_row->钻取选项] = [];
            }

            $chart_arr[$chart_id][$chart_code]['钻取模块'][$drill_row->钻取选项]['模块名称'] = $drill_row->钻取模块;
            $chart_arr[$chart_id][$chart_code]['钻取模块'][$drill_row->钻取选项]['钻取选项'] = $drill_row->钻取选项;
            $chart_arr[$chart_id][$chart_code]['钻取模块'][$drill_row->钻取选项]['钻取字段'] = $drill_row->钻取字段;
            $chart_arr[$chart_id][$chart_code]['钻取模块'][$drill_row->钻取选项]['钻取条件'] = $drill_row->钻取条件;
            $chart_arr[$chart_id][$chart_code]['钻取模块'][$drill_row->钻取选项]['图形模块'] = $drill_row->图形模块;

            $chart_arr[$chart_id][$chart_code]['钻取模块'][$drill_row->钻取选项]['继承条件'] = $chart_drill_cond_str;
            $chart_arr[$chart_id][$chart_code]['钻取模块'][$drill_row->钻取选项]['继承标题'] = $chart_drill_title_str;

            // 存入session
            $session_arr = [];
            $session_arr[sprintf('%s^%s-chart_drill_cond_str',$menu_id,$drill_row->图形模块)] = $chart_drill_cond_str;
            $session_arr[sprintf('%s^%s-chart_drill_title_str',$menu_id,$drill_row->图形模块)] = $chart_drill_title_str;
            $session = \Config\Services::session();
            $session->set($session_arr);
        }

        return $chart_arr;
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 取出filter条件
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function get_filter($filter_name, $filter_arg)
    {
        $name = $filter_name;
        $filter_type = $filter_arg->filterType;
        $type = $filter_arg->type;
        $filter = $filter_arg->filter;

        $filter_str = '1=1';
        if ($filter_type == 'text')
        {
            switch ($type)
            {
                case 'equals':
                    $filter_str = sprintf('%s="%s"', $name, $filter);
                    break;
                case 'notEquals':
                    $filter_str = sprintf('%s!="%s"', $name, $filter);
                    break;
                case 'contains':
                    $filter_str = sprintf('instr(%s,"%s")', $name, $filter);
                    break;
                case 'notContains':
                    $filter_str = sprintf('instr(%s,"%s")=0', $name, $filter);
                    break;
                case 'startsWith':
                    $filter_str = sprintf('left(%s,length("%s"))="%s"', $name, $filter, $filter);
                    break;
                case 'endsWith':
                    $filter_str = sprintf('right(%s,length("%s"))="%s"', $name, $filter, $filter);
                    break;
                case 'blank':
                    $filter_str = sprintf('%s=""', $name);
                    break;
                case 'notBlank':
                    $filter_str = sprintf('%s!=""', $name);
                    break;
                default:
                    $filter_str = '1=1';
                    break;
            }
        }
        else if ($filter_type == 'number')
        {
            switch ($type)
            {
                case 'equals':
                    $filter_str = sprintf('%s=%d', $name, $filter);
                    break;
                case 'notEquals':
                    $filter_str = sprintf('%s!=%d', $name, $filter);
                    break;
                case 'lessThan':
                    $filter_str = sprintf('%s<%d', $name, $filter);
                    break;
                case 'greaterThan':
                    $filter_str = sprintf('%s>%d', $name, $filter);
                    break;
                case 'lessThanOrEqual':
                    $filter_str = sprintf('%s<=%d', $name, $filter);
                    break;
                case 'greaterThanOrEqual':
                    $filter_str = sprintf('%s>=%d', $name, $filter);
                    break;
                default:
                    $filter_str = '1=1';
                    break;
            }
        }

        return $filter_str;
    }
}