<?php

/* v1.1.1.1.202204261615, from office */

namespace App\Models;
use CodeIgniter\Model;

class Mcommon extends Model
{
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 通用查询
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function select($sql)
    {
        $db = db_connect('btdc');
        $query = $db->query($sql);
        $db->close();

        return $query;
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 通用更新
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function modify($sql)
    {
        $db = db_connect('btdc');

        $db->query($sql);
        $num = $db->affectedRows();

        $db->close();

        return $num;
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 通用插入
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function add($table, $data, $fld_arr)
    {
        $db = db_connect('btdc');

        foreach ($data as $arr)
        {
            if (!array_diff($arr, $fld_arr)) continue;  //表头
            $arr = array_combine($fld_arr, $arr);  //修改键名
            $db->table($table)->insert($arr);
            $num = $db->affectedRows();
        }

        $db->close();

        return $num;
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 通用执行sql
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function exec($sql)
    {
        $db = db_connect('btdc');

        $db->query($sql);
        $num = $db->affectedRows();

        $db->close();

        return $num;
    }
}
