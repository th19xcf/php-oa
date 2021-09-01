<?php

/* v1.0.0.0.2021080310000, from home */

namespace App\Models;
use CodeIgniter\Model;

class Mlogin extends Model
{
    public function checkin($user_id, $pswd)
    {
        $db = db_connect('btdc');
        $sql = 'select 员工编号,姓名 from def_user
                where 工号=? and 密码=? ';
/*
        $sql = ' select ly_func.func_id,ly_func.func1_desc,ly_func.func2_desc,ly_func.func3_desc,ly_func.func_link,ly_func.sn,ly_agent_role.role_id 
        from ly_agent_role,ly_func,ly_role_func 
        where ly_agent_role.agent_id="lizheng"
            and ly_agent_role.role_id=ly_role_func.role_id and ly_role_func.func_id = ly_func.func_id 
            and ly_agent_role.team_id="bj27" 
            and ly_agent_role.team_id = ly_role_func.team_id order by sn 
            limit 1';
*/
        $query = $db->query($sql, array($user_id,$pswd));
        $results = $query->getResult();

        $db->close();

        return $results;
    }
}
