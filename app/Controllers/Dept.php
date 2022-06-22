<?php
/* v1.1.0.1.2022060221930, from home */

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
    // 部门
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function init($menu_id='')
    {
        // 从session中取出数据
        $session = \Config\Services::session();
        $dept_authz = $session->get($menu_id.'-dept_authz');

        $sql = sprintf('
            select 一级部门,二级部门,三级部门,四级部门,五级部门,六级部门
            from view_dept
            where instr(部门编码,"%s")            
                and instr(五级部门,"热线")
            order by 一级部门,二级部门,三级部门,四级部门,五级部门,六级部门',
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
        $dept_6_arr = [];

        // 本级
        $items_1 = [];
        $items_2 = [];
        $items_3 = [];
        $items_4 = [];
        $items_5 = [];
        $items_6 = [];



        $ii = 0;
        $item = [];

        foreach ($results as $row)
        {
            $ii ++;

            if ($row->六级部门 != '')
            {
                $item = [];
                $item['5级'] = '5级^' . $row->五级部门;
                $item['id'] = '6级^' . $row->六级部门;
                $item['value'] = $row->六级部门;

                $items_6[$row->六级部门] = $item;
            }
            if ($row->五级部门 != '')
            {
                $item = [];
                $item['4级'] = '4级^' . $row->四级部门;
                $item['id'] = '5级^' . $row->五级部门;
                $item['value'] = $row->五级部门;

                $items_5[$row->五级部门] = $item;
            }
            if ($row->四级部门 != '')
            {
                $item = [];
                $item['3级'] = '3级^' . $row->三级部门;
                $item['id'] = '4级^' . $row->四级部门;
                $item['value'] = $row->四级部门;

                $items_4[$row->四级部门] = $item;
            }
            if ($row->三级部门 != '')
            {
                $item = [];
                $item['2级'] = '2级^' . $row->二级部门;
                $item['id'] = '3级^' . $row->三级部门;
                $item['value'] = $row->三级部门;

                $items_3[$row->三级部门] = $item;
            }
            if ($row->二级部门 != '')
            {
                $item = [];
                $item['1级'] = '1级^' . $row->一级部门;
                $item['id'] = '2级^' . $row->二级部门;
                $item['value'] = $row->二级部门;

                $items_2[$row->二级部门] = $item;
            }
            if ($row->一级部门 != '')
            {
                $item = [];
                $item['id'] = '1级^' . $row->一级部门;
                $item['value'] = $row->一级部门;

                $items_1[$row->一级部门] = $item;
            }
        }

        foreach ($items_5 as $key => $value)
        {
            $dept = [];
            $dept['4级'] = $value['4级'];
            $dept['id'] = '5级^' . $key;
            $dept['value'] = $key;

            $dept['items'] = [];
            foreach ($items_6 as $kk => $vv)
            {
                if ($dept['id'] == $vv['5级'])
                {
                    array_push($dept['items'], $vv);
                }
            }

            array_push($dept_5_arr, $dept);
        }

        foreach ($items_4 as $key => $value)
        {
            $dept = [];
            $dept['3级'] = $value['3级'];
            $dept['id'] = '4级^' . $key;
            $dept['value'] = $key;

            $dept['items'] = [];
            foreach ($dept_5_arr as $kk => $vv)
            {
                if ($dept['id'] == $vv['4级'])
                {
                    array_push($dept['items'], $vv);
                }
            }

            array_push($dept_4_arr, $dept);
        }

        foreach ($items_3 as $key => $value)
        {
            $dept = [];
            $dept['2级'] = $value['2级'];
            $dept['id'] = '3级^' . $key;
            $dept['value'] = $key;

            $dept['items'] = [];
            foreach ($dept_4_arr as $kk => $vv)
            {
                if ($dept['id'] == $vv['3级'])
                {
                    array_push($dept['items'], $vv);
                }
            }

            array_push($dept_3_arr, $dept);
        }

        foreach ($items_2 as $key => $value)
        {
            $dept = [];
            $dept['1级'] = $value['1级'];
            $dept['id'] = '2级^' . $key;
            $dept['value'] = $key;

            $dept['items'] = [];
            foreach ($dept_3_arr as $kk => $vv)
            {
                if ($dept['id'] == $vv['2级'])
                {
                    array_push($dept['items'], $vv);
                }
            }

            array_push($dept_2_arr, $dept);
        }

        foreach ($items_1 as $key => $value)
        {
            $dept = [];
            $dept['id'] = '1级^' . $key;
            $dept['value'] = $key;

            $dept['items'] = [];
            foreach ($dept_2_arr as $kk => $vv)
            {
                if ($dept['id'] == $vv['1级'])
                {
                    array_push($dept['items'], $vv);
                }
            }

            array_push($dept_1_arr, $dept);
        }

        $send['func_id'] = $menu_id;
        $send['dept_json'] = json_encode($dept_1_arr);

        echo view('Vdept.php', $send);
    }
}