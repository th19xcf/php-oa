<?php

/* v1.0.0.1.202110071730, from home */

namespace App\Models;
use CodeIgniter\Model;

class Mlogin extends Model
{
    public function checkin($user_id, $pswd)
    {
        $db = db_connect('btdc');
        $sql = 'select 员工编号,姓名 from def_user
                where 工号=? and 密码=? ';
        $query = $db->query($sql, array($user_id,$pswd));
        $results = $query->getResult();

        $db->close();

        return $results;
    }
}
