<?php

/* v1.1.2.1.202204302115, from home */

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
    // 使用事务方式插入
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function add_by_trans($table, $data, $fld_arr)
    {
        $db = db_connect('btdc');

        $db->transStart();

        $num = 0;
        foreach ($data as $arr)
        {
            if (!array_diff($arr, $fld_arr)) continue;  //表头
            $arr = array_combine($fld_arr, $arr);  //修改键名
            $db->table($table)->insert($arr);
            $num = $num + $db->affectedRows();
        }

        $db->transComplete();

        if ($this->db->transStatus() == FALSE)
        {
            log_message('error', '事务执行错误');
            return -1;
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
