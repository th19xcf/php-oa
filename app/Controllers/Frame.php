<?php

/* v1.2.0.1.202110172320, from home */

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

        $columns_str = '';
        $columns_arr = array();
        $cond_arr = array();  // 条件数据

        foreach ($results as $row)
        {
            $columns_arr[$row->列名]['id'] = $row->列名;
            $columns_arr[$row->列名]['header']['text'] = $row->列名;
            //$columns_arr[$row->列类型]['header']['text'] = $row->列类型;

            if ($columns_str != '')
            {
                $columns_str = $columns_str . ',';
            }
            $columns_str = $columns_str . $row->字段 . ' as ' . $row->列名;

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

            array_push($cond_arr, $cond);
        }

        // 存入session
        $session_arr = [];
        $session_arr['func_id_'.$menu_id] = $columns_str;

        $session = \Config\Services::session();
        $session->set($session_arr);

        $sql = sprintf('select %s from biz_income', $columns_str);
        $results = $model->get_data($sql);

        $send['column_json'] = json_encode($columns_arr);
        $send['data_json'] = json_encode($results);
        $send['cond_json'] = json_encode($cond_arr);
        $send['func_id'] = $menu_id;

        echo view('Vgrid_json.php', $send);
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

        for ($ii=0; $ii<count($cond_arr); $ii++)
        {
            $cond = $cond_arr[$ii];
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
        $columns_str = $session->get('func_id_'.$menu_id);

        $sql = sprintf('select %s from biz_income', $columns_str);
        if ($where != '')
        {
            $sql = $sql . ' where ' . $where;
        }
        if ($group != '')
        {
            $sql = $sql . ' group by ' . $group;
        }

        $model = new Mframe();
        $results = $model->get_data($sql);

        exit(json_encode($results));
    }
}
