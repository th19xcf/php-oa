<?php

/* v1.1.2.1.202202241335, from office */

namespace App\Controllers;
use \CodeIgniter\Controller;
use App\Models\Mframe;

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
        $Arg['NextPage'] = base_url('login/checkin');
        echo view('Vlogin.php', $Arg);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 登录校验
	//+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function checkin()
    {
        $user_id = $this->request->getPost('userid');
        $pswd = $this->request->getPost('userpwd');

        $sql = sprintf(
            'select 员工编号,姓名,身份证号,角色
            from def_user
            where 工号="%s" and 密码="%s" ', $user_id, $pswd);

        $model = new Mframe();
        $query = $model->select($sql);
        $results = $query->getResult();

        if ($results==null)
        {
            $Arg['msg'] = '工号或密码错误, 请重新输入！';
            exit('2');
        }

        foreach ($results as $row)
        {
            // 存入session
            $session_arr = [];
            $session_arr['user_id'] = $row->员工编号;
            $session_arr['user_name'] = $row->姓名;
            $session_arr['user_role'] = $row->角色;

            $session = \Config\Services::session();
            $session->set($session_arr);

            exit('1');
        }
    }
}