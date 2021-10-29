<?php

/* v1.4.1.1.202110282355, from home */

namespace App\Controllers;
use \CodeIgniter\Controller;
use App\Models\Mframe;

class Frame extends Controller
{
    public function __construct()
    {
        helper(['form', 'url']);
    }

    public function index()
    {
        echo view('Vframe.php');
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 生成页面菜单树
	//+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function get_menu()
    {
        $model = new Mframe();
        $results = $model->get_menu('lizheng', 'bj27');

        $json = array();

        foreach ($results as $row)
        {
            #附加功能编码回传使用
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
    // 通用条件查询模块
	//+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function get_condition($menu_id='')
    {
        $sql = sprintf('
            select 查询模块,列名,列类型,字段名,查询名,对象,if(类型 is null,"",类型) as 类型
            from view_function 
            where 功能编码=%s
            group by 列名', $menu_id);

        $model = new Mframe();
        $results = $model->select($sql);

        $select_str = '';
        $grid_col_arr = array();  // 客户端grid列信息
        $grid_cond_arr = array();  // 条件设置信息
        $columns_arr = array();  // 列信息

        foreach ($results as $row)
        {
            // 客户端grid列信息
            $grid_col_arr[$row->列名]['header'] = array();
            $grid_col_arr[$row->列名]['options'] = array();

            $grid_col_arr[$row->列名]['id'] = $row->列名;

            $grid_col_arr[$row->列名]['header']['text'] = $row->列名;
            $grid_col_arr[$row->列名]['header']['content'] = $row->类型;

            if ($row->列类型 == '数字')
            {
                $grid_col_arr[$row->列名]['type'] = 'number';
            }
            else
            {
                $grid_col_arr[$row->列名]['type'] = 'string';
            }

            if ($row->类型 == '下拉')
            {
                $rslt = $model->select(sprintf('select 对象值 from def_object where 对象名称="%s" order by 顺序',$row->对象));
                foreach($rslt as $vv)
                {
                    array_push($grid_col_arr[$row->列名]['options'], $vv->对象值);
                }
            }

            // 查询数据表对应的查询名
            if ($select_str != '')
            {
                $select_str = $select_str . ',';
            }
            $select_str = $select_str . $row->查询名 . ' as ' . $row->列名;

            // 设置条件使用
            $cond = array();
            $cond['列名'] = $row->列名;
            $cond['类型'] = $row->列类型;
            $cond['汇总'] = false;
            $cond['平均'] = false;
            $cond['条件1'] = '';
            $cond['参数1'] = '';
            $cond['条件关系'] = '';
            $cond['条件2'] = '';
            $cond['参数2'] = '';

            array_push($grid_cond_arr, $cond);

            // 新增记录使用
            $arr = array();
            $arr['列名'] = $row->列名;
            $arr['类型'] = $row->列类型;
            $arr['字段名'] = $row->字段名;

            array_push($columns_arr, $arr);
        }

        // 取出查询模块对应的表配置
        $table_name = '';

        $sql = sprintf('
            select 表名 
            from view_function 
            where 功能编码=%s
            group by 功能编码', $menu_id);

        $results = $model->select($sql);
        foreach ($results as $row)
        {
            $table_name = $row->表名;
            break;
        }

        // 取出对象配置
        // 下标列名, 取值保存为str, 在js转换
        $objects_arr = array();  // 对象数据

        $sql = sprintf('
            select 列名,对象名称,对象值 
            from view_function 
            where 对象名称!="" and 功能编码=%s', $menu_id);

        $results = $model->select($sql);
        $col_name = '';

        foreach ($results as $row)
        {
            if ($col_name != $row->列名)
            {
                $objects_arr[$row->列名] = $row->对象值;
                $col_name = $row->列名;
            }
            else
            {
                $objects_arr[$row->列名] = $objects_arr[$row->列名] . ',' . $row->对象值;
            }
        }

        // 读出数据
        $sql = sprintf('select %s from %s', $select_str, $table_name);
        $results = $model->select($sql);

        // 存入session
        $session_arr = [];
        $session_arr[$menu_id.'select_str'] = $select_str;
        $session_arr[$menu_id.'-table_name'] = $table_name;
        $session_arr[$menu_id.'-columns_arr'] = $columns_arr;

        $session = \Config\Services::session();
        $session->set($session_arr);

        $send['col_json'] = json_encode($grid_col_arr);
        $send['data_json'] = json_encode($results);
        $send['cond_json'] = json_encode($grid_cond_arr);
        $send['object_json'] = json_encode($objects_arr);
        $send['func_id'] = $menu_id;

        echo view('Vgrid_json.php', $send);
    }

	//+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 通用条件设置模块
	//+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function set_condition($menu_id='')
    {
        //$data = $this->request->getBody();
        $grid_cond_arr = $this->request->getJSON(true);

        $where = '';
        $group = '';
        $average = '';

        for ($ii=0; $ii<count($grid_cond_arr); $ii++)
        {
            $cond = $grid_cond_arr[$ii];
            $cond_str = '';
            $cond_1 = '';
            $cond_2 = '';
            if ($cond['条件1'] != '')
            {
                switch ($cond['条件1'])
                {
                    case '大于':
                        $cond_1 = sprintf(' %s>%s ', $cond['列名'], $cond['参数1']);
                        break;
                    case '等于':
                        $cond_1 = sprintf(' %s=%s ', $cond['列名'], $cond['参数1']);
                        break;
                    case '小于':
                        $cond_1 = sprintf(' %s<%s ', $cond['列名'], $cond['参数1']);
                        break;
                    case '大于等于':
                        $cond_1 = sprintf(' %s>=%s ', $cond['列名'], $cond['参数1']);
                        break;
                    case '小于等于':
                        $cond_1 = sprintf(' %s<=%s ', $cond['列名'], $cond['参数1']);
                        break;
                    case '不等于':
                        $cond_1 = sprintf(' %s!=%s ', $cond['列名'], $cond['参数1']);
                        break;
                    case '包含':
                        $cond_1 = sprintf(' %s in (%s) ', $cond['列名'], $cond['参数1']);
                        break;
                    case '不包含':
                        $cond_1 = sprintf(' %s not in (%s) ', $cond['列名'], $cond['参数1']);
                        break;
                }
            }

            if ($cond['条件2'] != '')
            {
                switch ($cond['条件2'])
                {
                    case '大于':
                        $cond_2 = sprintf(' %s>%s ', $cond['列名'], $cond['参数2']);
                        break;
                    case '等于':
                        $cond_2 = sprintf(' %s=%s ', $cond['列名'], $cond['参数2']);
                        break;
                    case '小于':
                        $cond_2 = sprintf(' %s<%s ', $cond['列名'], $cond['参数2']);
                        break;
                    case '大于等于':
                        $cond_2 = sprintf(' %s>=%s ', $cond['列名'], $cond['参数2']);
                        break;
                    case '小于等于':
                        $cond_2 = sprintf(' %s<=%s ', $cond['列名'], $cond['参数2']);
                        break;
                    case '不等于':
                        $cond_2 = sprintf(' %s!=%s ', $cond['列名'], $cond['参数2']);
                        break;
                    case '包含':
                        $cond_2 = sprintf(' %s in (%s) ', $cond['列名'], $cond['参数2']);
                        break;
                    case '不包含':
                        $cond_2 = sprintf(' %s not in (%s) ', $cond['列名'], $cond['参数2']);
                        break;
                }
            }

            switch ($cond['条件关系'])
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

            if ($cond['汇总'] == true)
            {
                if ($group != '') $group = $group . ' , ';
                $group = $group . $cond['列名'];
            }
            if ($where != '') $where = $where . ' and ';
            $where = $where . $cond_str;
        }

        // 从session中取出数据
        $session = \Config\Services::session();
        $table_name = $session->get($menu_id.'-table_name');
        $select_str = $session->get($menu_id.'select_str');

        $sql = sprintf('select %s from %s', $select_str, $table_name);
        if ($where != '')
        {
            $sql = $sql . ' where ' . $where;
        }
        if ($group != '')
        {
            $sql = $sql . ' group by ' . $group;
        }

        $model = new Mframe();
        $results = $model->select($sql);

        exit(json_encode($results));
    }

	//+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 增加新行
	//+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function add_row($menu_id='')
    {
        $row_arr = $this->request->getJSON(true);

        // 从session中取出数据
        $session = \Config\Services::session();
        $table_name = $session->get($menu_id.'-table_name');
        $columns_arr = $session->get($menu_id.'-columns_arr');

        $flds_str = '';
        $values_str = '';
        foreach ($columns_arr as $col)
        {
            if ($flds_str != '')
            {
                $flds_str = $flds_str . ',';
                $values_str = $values_str . ',';
            }
            $flds_str = $flds_str . $col['字段名'];
            switch ($col['类型'])
            {
                case '字符':
                    $values_str = sprintf('%s"%s"', $values_str, $row_arr[$col['列名']]);
                    break;
                case '数字':
                    $values_str = sprintf('%s%.2f', $values_str, $row_arr[$col['列名']]);
                    break;
            }
        }

        $sql = sprintf('insert into %s (%s) values (%s)', $table_name, $flds_str, $values_str);

        $model = new Mframe();
        $num = $model->add($sql);

        exit($num);
    }
}
