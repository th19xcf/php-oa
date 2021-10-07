<?php

/* v1.0.0.1.202110071730, from home */

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
    public function get_condition($menu_id)
    {
        $db = db_connect('btdc');

        $sql = 'select 条件模块,对象名称,对象类型,行位置,列位置,变量名称,字段名称
            from def_condition
            where 条件模块 in
            (
                select 条件模块
                from def_function
                where 功能编码=?
            )
            order by 行位置,列位置';

        $query = $db->query($sql, $menu_id);
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
