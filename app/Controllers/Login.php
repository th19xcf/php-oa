<?php

/* v2.1.1.1.202208262355, from home */

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
            where 工号="%s" and 密码="%s" ',
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

        foreach ($results as $row)
        {
            if ($row->员工属地 != $company_id)
            {
                $Arg['msg'] = '属地错误！';
                exit('10');
            }

            // 存入session
            $session_arr = [];
            $session_arr['user_id'] = $row->员工编号;
            $session_arr['user_workid'] = $row->工号;
            $session_arr['user_name'] = $row->姓名;
            $session_arr['user_role'] = $row->角色;
            $session_arr['user_pswd'] = $pswd;
            $session_arr['user_location'] = $row->员工属地;

            $session = \Config\Services::session();
            $session->set($session_arr);

            $model->sql_log('登录成功','',sprintf('角色=%s',$row->角色));

            exit('1');
        }
    }
}