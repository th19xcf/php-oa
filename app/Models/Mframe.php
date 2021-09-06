<?php

/* v1.0.0.1.202109061700, from office */

namespace App\Models;
use CodeIgniter\Model;

class Mframe extends Model
{
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 根据不同用户, 构建menu
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function GetMenu($agent_id, $role_id)
    {
        $db = db_connect('btdc');

        $sql = 'select * from def_function';
        $query = $db->query($sql);
        $results = $query->getResult();

        $db->close();

        return $results;        
    }
}
