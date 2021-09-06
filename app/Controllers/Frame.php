<?php

/* v1.0.0.1.202109061700, from office */

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

    public function GetMenu()
    {
        $model = new Mframe();
        $results = $model->GetMenu('lizheng', 'bj27');

        $json = $rslt = array();
        $i = $j = $k = 0;

        foreach ($results as $row)
        {
            $row->功能模块 = $row->功能模块 . '?func=' . urlencode($row->一级菜单);
            $children = array(
                "text" => "<a href='javascript:void(0);'tag='" . $row->功能模块 . "'onclick='goto(" . $row->功能名称 . ")'>" . $row->func3_desc . "</a>",
                "expanded" => true
            );

            $json[$row->一级菜单]['text'] = $row->一级菜单;
            $json[$row->一级菜单]['expanded'] = false;
            $json[$row->一级菜单]['children'][] = $children;
        }

        echo json_encode($json, 320);//256+64,不转义中文+反斜杠
    }
}