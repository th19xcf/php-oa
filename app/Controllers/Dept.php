<?php
/* v1.2.0.1.202207022020, from home */

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
    public function init_1($menu_id='')
    {
        // 从session中取出数据
        $session = \Config\Services::session();
        $dept_authz = $session->get($menu_id.'-dept_authz');

        $sql = sprintf('
            select 部门编码,一级部门,二级部门,三级部门,四级部门,五级部门,六级部门
            from view_dept
            where instr(部门编码,"%s")            
                #and instr(五级部门,"热线")
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
                $item['id'] = '6级^' . $row->六级部门 . '^' . $row->部门编码;
                $item['value'] = $row->六级部门;
                $item['guid'] = $row->部门编码;

                $items_6[$row->六级部门] = $item;
            }
            if ($row->五级部门 != '')
            {
                $item = [];
                $item['4级'] = '4级^' . $row->四级部门;
                $item['id'] = '5级^' . $row->五级部门 . '^' . $row->部门编码;
                $item['value'] = $row->五级部门;
                $item['guid'] = $row->部门编码;

                $items_5[$row->五级部门] = $item;
            }
            if ($row->四级部门 != '')
            {
                $item = [];
                $item['3级'] = '3级^' . $row->三级部门;
                $item['id'] = '4级^' . $row->四级部门 . '^' . $row->部门编码;
                $item['value'] = $row->四级部门;
                $item['guid'] = $row->部门编码;

                $items_4[$row->四级部门] = $item;
            }
            if ($row->三级部门 != '')
            {
                $item = [];
                $item['2级'] = '2级^' . $row->二级部门;
                $item['id'] = '3级^' . $row->三级部门 . '^' . $row->部门编码;
                $item['value'] = $row->三级部门;
                $item['guid'] = $row->部门编码;

                $items_3[$row->三级部门] = $item;
            }
            if ($row->二级部门 != '')
            {
                $item = [];
                $item['1级'] = '1级^' . $row->一级部门;
                $item['id'] = '2级^' . $row->二级部门 . '^' . $row->部门编码;
                $item['value'] = $row->二级部门;
                $item['guid'] = $row->部门编码;

                $items_2[$row->二级部门] = $item;
            }
            if ($row->一级部门 != '')
            {
                $item = [];
                $item['id'] = '1级^' . $row->一级部门;
                $item['value'] = $row->一级部门 . '^' . $row->部门编码;
                $item['guid'] = $row->部门编码;

                $items_1[$row->一级部门] = $item;
            }
        }

        foreach ($items_5 as $key => $value)
        {
            $dept = [];
            $dept['4级'] = $value['4级'];
            $dept['id'] = $value['id'];
            $dept['value'] = $key;
            $dept['items'] = [];

            foreach ($items_6 as $kk => $vv)
            {
                if ($vv['5级'] == ('5级^' . $dept['value']))
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
            $dept['id'] = $value['value'];
            $dept['value'] = $key;
            $dept['items'] = [];

            foreach ($dept_5_arr as $kk => $vv)
            {
                if ($vv['4级'] == ('4级^' . $dept['value']))
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
            $dept['id'] = $value['id'];;
            $dept['value'] = $key;
            $dept['items'] = [];

            foreach ($dept_4_arr as $kk => $vv)
            {
                if ($vv['3级'] == ('3级^' . $dept['value']))
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
            $dept['id'] = $value['id'];;
            $dept['value'] = $key;
            $dept['items'] = [];

            foreach ($dept_3_arr as $kk => $vv)
            {
                if ($vv['2级'] == ('2级^' . $dept['value']))
                {
                    array_push($dept['items'], $vv);
                }
            }

            array_push($dept_2_arr, $dept);
        }

        foreach ($items_1 as $key => $value)
        {
            $dept = [];
            $dept['id'] = $value['id'];;
            $dept['value'] = $key;
            $dept['items'] = [];

            foreach ($dept_2_arr as $kk => $vv)
            {
                if ($vv['1级'] == ('1级^' . $dept['value']))
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

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 部门
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

        /*
        $dept = [];
        $dept['id'] = '1级';
        $dept['value'] = '电发';
        $item['higher'] = '';
        $dept['items'] = [];

        $item = [];
        $item['id'] = '2级';
        $item['value'] = '呼叫中心';
        $item['higher'] = '';
        array_push($dept['items'], $item);

        $dept_arr = [];
        array_push($dept_arr, $dept);
        */

        $send['func_id'] = $menu_id;
        $send['dept_json'] = json_encode($dept_arr);

        echo view('Vdept.php', $send);
    }
}