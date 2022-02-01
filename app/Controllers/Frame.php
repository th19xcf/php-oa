<?php
/* v3.3.3.1.202202012325, from home */
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
        $select_str = '';
        $primary_key = '';
        $data_col_arr = array();  // 客户端data_grid列信息
        $columns_arr = array();  // 列信息

        $update_value_arr = array();  // 客户端update_grid值信息
        $cond_value_arr = array();  // 条件设置信息

        // 客户端data_grid列信息,手工增加选取列和序号列
        $data_col_arr['选取']['field'] = '选取';
        $data_col_arr['选取']['width'] = 100;
        $data_col_arr['选取']['resizable'] = true;
        $data_col_arr['选取']['headerCheckboxSelection'] = true;
        $data_col_arr['选取']['checkboxSelection'] = true;

        $data_col_arr['序号']['field'] = '序号';
        $data_col_arr['序号']['width'] = 90;
        $data_col_arr['序号']['resizable'] = true;
        $data_col_arr['序号']['sortable'] = true;

        $object_arr = array();  // 下拉选择的对象值

        $sql = sprintf(
            'select 查询模块,列名,列类型,列宽度,字段名,查询名,对象,可筛选,主键,赋值类型
            from view_function 
            where 功能编码=%s and 列顺序>0
            group by 列名
            order by 列顺序', $menu_id);

        $model = new Mframe();
        $query = $model->select($sql);
        $results = $query->getResult();

        foreach ($results as $row)
        {
            if ($row->主键 != 0)
            {
                $primary_key = $row->列名;
            }

            // 列信息
            $arr = array();

            $arr['列名'] = $row->列名;
            $arr['类型'] = $row->列类型;
            $arr['字段名'] = $row->字段名;
            $arr['主键'] = $row->主键;
            $arr['赋值类型'] = $row->赋值类型;
            $arr['对象'] = $row->对象;

            array_push($columns_arr, $arr);

            // 客户端data_grid列信息
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
            if ($row->主键 != 0)
            {
                $data_col_arr[$row->列名]['hide'] = true;
            }
            if ($row->列类型 == '数值')
            {
                $data_col_arr[$row->列名]['filter'] = 'agNumberColumnFilter';
            }

            // 查询数据表对应的查询名
            if ($select_str != '')
            {
                $select_str = $select_str . ',';
            }
            $select_str = $select_str . $row->查询名 . ' as ' . $row->列名;

            // 客户端update_grid值
            if ($row->主键 != 0) continue;
            $value_arr = array();
            $value_arr['列名'] = $row->列名;
            $value_arr['字段名'] = $row->字段名;
            $value_arr['列类型'] = $row->列类型;
            $value_arr['取值'] = '';

            if ($row->赋值类型 == '下拉')
            {
                $object_arr[$row->列名] = array();
                $object_arr[$row->列名][0] = '';

                $qry = $model->select(sprintf('select 对象值 from def_object where 对象名称="%s" order by 顺序',$row->对象));
                $rslt = $qry->getResult();
                foreach($rslt as $vv)
                {
                    array_push($object_arr[$row->列名], $vv->对象值);
                }
            }

            array_push($update_value_arr, $value_arr);

            // 客户端cond_grid信息
            $cond = array();
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

            array_push($cond_value_arr, $cond);
        }

        // 取出查询模块对应的表配置
        $table_name = '';

        $sql = sprintf('
            select 表名 
            from view_function 
            where 功能编码=%s
            group by 功能编码', $menu_id);

        $query = $model->select($sql);
        $results = $query->getResult();
        foreach ($results as $row)
        {
            $table_name = $row->表名;
            break;
        }

        // 读出数据
        $sql = sprintf('select "" as 选取,(@i:=@i+1) as 序号,%s 
            from %s,(select @i:=0) as xh', 
            $select_str, $table_name);
        $query = $model->select($sql);
        $results = $query->getResult();

        // 存入session
        $session_arr = [];
        $session_arr[$menu_id.'-select_str'] = $select_str;
        $session_arr[$menu_id.'-query_str'] = $sql;
        $session_arr[$menu_id.'-table_name'] = $table_name;
        $session_arr[$menu_id.'-columns_arr'] = $columns_arr;
        $session_arr[$menu_id.'-primary_key'] = $primary_key;

        $session = \Config\Services::session();
        $session->set($session_arr);

        $send['columns_json'] = json_encode($columns_arr);
        $send['data_col_json'] = json_encode($data_col_arr);
        $send['data_value_json'] = json_encode($results);
        $send['update_value_json'] = json_encode($update_value_arr);
        $send['cond_value_json'] = json_encode($cond_value_arr);
        $send['object_json'] = json_encode($object_arr);
        $send['func_id'] = $menu_id;
        $send['primary_key'] = $primary_key;

        echo view('Vgrid_aggrid.php', $send);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 通用条件设置模块
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function set_condition($menu_id='')
    {
        //$data = $this->request->getBody();
        $cond_arr = $this->request->getJSON(true);

        $where = '';
        $group = '';
        $average = '';

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
                    if ($opt_1!='in' && $opt_1!='not in')
                    {
                        $cond_1 = sprintf(' %s%s"%s" ', $cond['fld_name'], $opt_1, $cond['arg_1']);
                    }
                    else
                    {
                        $cond_1 = sprintf(' %s %s ("%s") ', $cond['fld_name'], $opt_1, $cond['arg_1']);
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
                    if ($opt_2!='in' && $opt_2!='not in')
                    {
                        $cond_2 = sprintf(' %s%s"%s" ', $cond['fld_name'], $opt_2, $cond['arg_2']);
                    }
                    else
                    {
                        $cond_2 = sprintf(' %s %s ("%s") ', $cond['fld_name'], $opt_2, $cond['arg_2']);
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

            if ($cond['group'] == true)
            {
                if ($group != '') $group = $group . ' , ';
                $group = $group . $cond['fld_name'];
            }
            if ($where != '') $where = $where . ' and ';
            $where = $where . $cond_str;
        }

        // 从session中取出数据
        $session = \Config\Services::session();
        $table_name = $session->get($menu_id.'-table_name');
        $select_str = $session->get($menu_id.'-select_str');

        // 读出数据
        $sql = sprintf('select "" as 选取,(@i:=@i+1) as 序号,%s from %s,(select @i:=0) as xh', 
            $select_str, $table_name);

        //$sql = sprintf('select %s from %s', $select_str, $table_name);
        if ($where != '')
        {
            $sql = $sql . ' where ' . $where;
        }
        if ($group != '')
        {
            $sql = $sql . ' group by ' . $group;
        }

        $model = new Mframe();
        $query = $model->select($sql);
        $results = $query->getResult();

        exit(json_encode($results));
    }

	//+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 更新行记录
	//+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function update_row($menu_id='')
    {
        $row_arr = $this->request->getJSON(true);

        // 从session中取出数据
        $session = \Config\Services::session();
        $table_name = $session->get($menu_id.'-table_name');
        $primary_key = $session->get($menu_id.'-primary_key');

        $set = '';
        $where = '';
        foreach ($row_arr as $row)
        {
            if ($row['fld_name'] == $primary_key)
            {
                $where = sprintf('%s in (%s)', $row['fld_name'], $row['value']);
            }
            else
            {
                if ($set == '')
                {
                    $set = sprintf('%s="%s"', $row['fld_name'], $row['value']);
                }
                else
                {
                    $set = sprintf('%s,%s=%s', $set, $row['fld_name'], $row['value']);
                }
            }
        }

        $sql = sprintf('update %s set %s where %s', $table_name, $set, $where);

        $model = new Mframe();
        $num = $model->modify($sql);

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
        $table_name = $session->get($menu_id.'-table_name');

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

        $sql = sprintf('insert into %s (%s) values (%s)', $table_name, $flds_str, $values_str);

        $model = new Mframe();
        $num = $model->add($sql);

        exit($num);
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

        $model = new Mframe();
        $query = $model->select($query_str);

        header('content-type:application/vnd.ms-excel; charset=gbk');
        header('content-disposition:attachment;filename=aa.xls');
        header('Accept-Ranges: bytes');

        exit($table->generate($query));
    }
}
