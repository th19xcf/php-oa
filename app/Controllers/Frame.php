<?php

/* v1.1.0.1.202110132330, from home */

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
        $model = new Mframe();
        $results = $model->get_column($menu_id);

        $column_arr = array();
        foreach ($results as $row)
        {
            $column_arr[$row->列名]['id'] = $row->列名;
            $column_arr[$row->列名]['header']['text'] = $row->列名;
        }

        $fld_str = '';
        $data_arr = array();
        foreach ($results as $row)
        {
            $data_arr[$row->列名]['id'] = $row->列名;
            $data_arr[$row->列名]['header']['text'] = $row->列名;

            if ($fld_str != '') $fld_str = $fld_str . ',';
            $fld_str = $fld_str . $row->字段 . ' as ' . $row->列名;
        }

        $sql = sprintf('select %s from biz_income', $fld_str);
        $results = $model->get_data($sql);

        $send['column_json'] = json_encode($column_arr);
        $send['data_json'] = json_encode($results);

        echo view('Vgrid_json.php', $send);
    }

	//+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 通用条件设置模块
	//+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function set_condition($menu_id='')
    {
        $model = new Mframe();
        $results = $model->get_condition($menu_id);

        $condition = [];
        foreach ($results as $row)
        {
            $condition[$row->字段名称] = $this->request->getPost($row->字段名称);
        }

        #echo json_encode($json, 320);  //256+64,不转义中文+反斜杠
    }
}
