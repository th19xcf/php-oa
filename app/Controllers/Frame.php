<?php

/* v1.0.0.1.202110071730, from home */

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
        $results = $model->get_condition($menu_id);

        if ($results==null)
        {
            echo view('Vcond_error.php');
            return;
        }

        $Arg['title'] = $menu_id;
        $Arg['NextPage'] = base_url('Frame/set_condition/' . $menu_id);
        echo view('Vcond_head.php', $Arg);

        echo '<body>';
        echo form_open('Frame/set_condition/' . $menu_id);
        echo '<table class="table_form" align="center">';
        echo '<tr>';
        echo '<td class="type0" colspan="4" align="center"> 选 择 条 件 </td>';
        echo '</tr>';

        $row_pos = 0;
        $col_pos = 0;
        foreach ($results as $row)
        {
            #行信息
            if ($row_pos != $row->行位置)
            {
                $row_pos = $row->行位置;
                $col_pos = 0;
                echo '<tr>';
            }

            #列信息
            echo '<td class="type1">' . $row->对象名称 . '</td>';
            switch ($row->对象类型)
            {
                case '文本':
                    echo '<td class="type3">' . form_input($row->变量名称) . '</td>';
                    break;
                case '下拉':
                    $rslts = $model->get_value($row->对象名称);
                    $options = [];
                    foreach ($rslts as $opt)
                    {
                        # []中设置下拉表单的value值
                        $options[$opt->对象值] = $opt->对象值;
                    }

                    echo '<td class="type3">' . form_dropdown($row->字段名称, $options, '') . '</td>';
                    break;
            }

            $col_pos ++;
            if($col_pos>2)
            {
                //错误;
            }
        }

        echo '<tr>';
    	echo '<td class="type2" colspan="4"> <input id="good" class="input_submit" type="submit" value="确 定" /> </td>';
        #echo '<td class="type2" colspan="4"> <button id="good" class="input_submit" type="submit">确 定</button> </td>';
    	echo '</tr>';
        echo '</table>';
        echo form_close();
        echo '</body>';

        echo view('Vcond_foot.php');
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

        return('1234');
    }

}