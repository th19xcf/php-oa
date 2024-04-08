<?php

/* v3.1.1.1.202404041245, from home */

namespace App\Controllers;
use \CodeIgniter\Controller;
use App\Models\Mcommon;

class Login extends Controller
{
    public function __construct()
    {
        helper(['form', 'url']);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 程序入口, 登录页面
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function index()
    {
        // 清除session
        $session = \Config\Services::session();
        $session->destroy();

        $Arg['NextPage'] = base_url('login/checkin');
        echo view('Vlogin.php', $Arg);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 登录校验
	//+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function checkin()
    {
        $company_id = $this->request->getPost('company_id');
        $user_workid = $this->request->getPost('userid');
        $pswd = $this->request->getPost('userpwd');

        $sql = sprintf('
            select 员工编号,姓名,身份证号,工号,角色,员工属地
            from def_user
            where 有效标识="1" and 工号="%s" and 密码="%s" ',
            $user_workid, $pswd);

        $model = new Mcommon();
        $query = $model->select($sql);
        $results = $query->getResult();

        if ($results == null)
        {
            $model->sql_log('登录失败');

            $Arg['msg'] = '工号或密码错误, 请重新输入！';
            exit('2');
        }

        // 验证员工属地
        foreach ($results as $row)
        {
            if (strpos($row->员工属地,$company_id) === false) continue;

            str_replace(' ', '', $row->角色);
            str_replace('，', ',', $row->角色);

            $role_arr = explode(',', $row->角色);

            $role_str = '';
            foreach ($role_arr as $role)
            {
                if ($role_str == '')
                {
                    $role_str = sprintf('"%s"', $role);
                }
                else
                {
                    $role_str = sprintf('%s,"%s"', $role_str, $role);
                }
            }

            str_replace(' ', '', $row->员工属地);
            str_replace('，', ',', $row->员工属地);

            $location_arr = explode(',', $row->员工属地);

            $location_str = '';
            foreach ($location_arr as $location)
            {
                if ($location_str == '')
                {
                    $location_str = sprintf('"%s"', $location);
                }
                else
                {
                    $location_str = sprintf('%s,"%s"', $location_str, $location);
                }
            }

            // 存入session
            $session_arr = [];
            $session_arr['user_id'] = $row->员工编号;
            $session_arr['user_workid'] = $row->工号;
            $session_arr['user_name'] = $row->姓名;
            $session_arr['user_role'] = $row->角色;
            $session_arr['user_role_str'] = $role_str;
            $session_arr['user_pswd'] = $pswd;
            $session_arr['user_location'] = $row->员工属地;
            $session_arr['user_location_str'] = $location_str;

            $session = \Config\Services::session();
            $session->set($session_arr);

            $model->sql_log('登录成功','',sprintf('角色=%s',$row->角色));

            exit('1');
        }

        $Arg['msg'] = '员工属地错误！';
        exit('10');
    }
}