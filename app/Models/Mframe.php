<?php

/* v1.0.0.1.202110132330, from home */

namespace App\Models;
use CodeIgniter\Model;

class Mframe extends Model
{
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 根据不同用户, 构建menu
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function get_menu($agent_id, $role_id)
    {
        $db = db_connect('btdc');

        $sql = 'select * from def_function where 菜单顺序>0 order by 菜单顺序';
        $query = $db->query($sql);
        $results = $query->getResult();

        $db->close();

        return $results;
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 通用查询
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function get_column($menu_id)
    {
        $db = db_connect('btdc');

        $sql = 'select 查询模块,列名,列类型,字段,顺序
            from def_query_column
            where 查询模块 in
            (
                select 查询模块
                from def_function
                where 功能编码=?
            )';

        $query = $db->query($sql, $menu_id);
        $results = $query->getResult();

        $db->close();

        return $results;
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 通用查询
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function get_data($sql)
    {
        $db = db_connect('btdc');

        $query = $db->query($sql);
        $results = $query->getResult();

        $db->close();

        return $results;
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 对象取值查询
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function get_value($obj_name)
    {
        $db = db_connect('btdc');

        $sql = 'select 对象名称,对象值
            from def_object
            where 对象名称=?
            order by 顺序';

        $query = $db->query($sql, $obj_name);
        $results = $query->getResult();

        $db->close();

        return $results;
    }

}
