<?php
/* v1.2.2.1.202207032305, from home */

namespace App\Controllers;
use \CodeIgniter\Controller;
use App\Models\Mcommon;

class Dept extends Controller
{
    public function __construct()
    {
        helper(['form', 'url']);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 部门组织结构
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function init($menu_id='')
    {
        // 从session中取出数据
        $session = \Config\Services::session();
        $dept_authz = $session->get($menu_id.'-dept_authz');

        $sql = sprintf('
            select 部门编码,部门名称,部门级别,上级部门
            from def_dept
            where instr(部门编码,"%s")
            order by 部门编码',
            $dept_authz);

        $model = new Mcommon();

        $query = $model->select($sql);
        $results = $query->getResult();

        $dept = '';
        $dept_1_arr = [];
        $dept_2_arr = [];
        $dept_3_arr = [];
        $dept_4_arr = [];
        $dept_5_arr = [];

        $ii = 0;
        $item_level = [];

        for ($ii=0; $ii<7; $ii++)
        {
            $item_level[$ii] = [];
        }

        foreach ($results as $row)
        {
            $ii ++;

            $item = [];
            $item['id'] = sprintf('%d级^%s^%s', $row->部门级别, $row->部门名称, $row->部门编码);
            $item['value'] = $row->部门名称;
            $item['guid'] = $row->部门编码;
            $item['level'] = $row->部门级别;
            $item['higher'] = $row->上级部门;

            array_push($item_level[$row->部门级别], $item);
        }

        foreach ($item_level[5] as $item)
        {
            $dept = [];
            $dept['id'] = $item['id'];
            $dept['value'] = $item['value'];
            $dept['higher'] = $item['higher'];
            $dept['items'] = [];

            foreach ($item_level[6] as $child)
            {
                if ($child['higher'] == $item['guid'])
                {
                    $child['value'] = sprintf('%s (0)',$child['value']);
                    array_push($dept['items'], $child);
                }
            }

            $dept['value'] = sprintf('%s (%d)',$item['value'], count($dept['items']));
            array_push($dept_5_arr, $dept);
        }

        foreach ($item_level[4] as $item)
        {
            $dept = [];
            $dept['id'] = $item['id'];
            $dept['value'] = $item['value'];
            $dept['higher'] = $item['higher'];
            $dept['items'] = [];

            foreach ($dept_5_arr as $child)
            {
                if ($child['higher'] == $item['guid'])
                {
                    array_push($dept['items'], $child);
                }
            }

            $dept['value'] = sprintf('%s (%d)',$item['value'], count($dept['items']));
            array_push($dept_4_arr, $dept);
        }

        foreach ($item_level[3] as $item)
        {
            $dept = [];
            $dept['id'] = $item['id'];
            $dept['value'] = $item['value'];
            $dept['higher'] = $item['higher'];
            $dept['items'] = [];

            foreach ($dept_4_arr as $child)
            {
                if ($child['higher'] == $item['guid'])
                {
                    array_push($dept['items'], $child);
                }
            }

            $dept['value'] = sprintf('%s (%d)',$item['value'], count($dept['items']));
            array_push($dept_3_arr, $dept);
        }

        if (count($dept_3_arr) > 0) $dept_arr = $dept_3_arr;

        foreach ($item_level[2] as $item)
        {
            $dept = [];
            $dept['id'] = $item['id'];
            $dept['value'] = $item['value'];
            $dept['higher'] = $item['higher'];
            $dept['items'] = [];

            foreach ($dept_3_arr as $child)
            {
                if ($child['higher'] == $item['guid'])
                {
                    array_push($dept['items'], $child);
                }
            }

            $dept['value'] = sprintf('%s (%d)',$item['value'], count($dept['items']));
            array_push($dept_2_arr, $dept);
        }

        if (count($dept_2_arr) > 0) $dept_arr = $dept_2_arr;

        foreach ($item_level[1] as $item)
        {
            $dept = [];
            $dept['id'] = $item['id'];
            $dept['value'] = $item['value'];
            $dept['higher'] = $item['higher'];
            $dept['items'] = [];

            foreach ($dept_2_arr as $child)
            {
                if ($child['higher'] == $item['guid'])
                {
                    array_push($dept['items'], $child);
                }
            }

            $dept['value'] = sprintf('%s (%d)',$item['value'], count($dept['items']));
            array_push($dept_1_arr, $dept);
        }

        if (count($dept_1_arr) > 0) $dept_arr = $dept_1_arr;

        $send['func_id'] = $menu_id;
        $send['dept_json'] = json_encode($dept_arr);

        echo view('Vdept.php', $send);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 部门操作
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function upkeep($menu_id='')
    {
        $cond_arr = $this->request->getJSON(true);

        $model = new Mcommon();

        if ($cond_arr['操作'] == '修改部门名称')
        {
            $sql = sprintf('update def_dept set 部门名称="%s" where 部门编码="%s"',
                $cond_arr['部门名称'], $cond_arr['部门编码']);
            $num = $model->exec($sql);
        }
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 自定义函数
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function json_data($status=200, $msg, $count, $data =[])
    {
        $res = [
            'status' => $status,
            'msg' => $msg,
            'number' => $count,
            'data' => $data
        ];

        echo json_encode($res);
        die;
    }
}
