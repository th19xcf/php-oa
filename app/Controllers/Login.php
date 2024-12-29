<?php

/* v4.1.1.1.202412292230, from home */

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
        $request = \Config\Services::request();
        $company_id = $request->getPost('company_id');
        $user_workid = $request->getPost('userid');
        $pswd = $request->getPost('userpwd');

        if ($company_id == '')
        {
            $Arg['msg'] = '员工属地错误！';
            exit('10');
        }

        $sql = '';
        if ($pswd == $user_workid.$user_workid)
        {
            $sql = sprintf('
                select 员工编号,工号,姓名,员工属地,部门编码,部门全称
                from def_user
                where 有效标识="1" and 员工属地="%s" and 工号="%s"',
                $company_id, $user_workid);
        }
        else
        {
            $sql = sprintf('
                select 员工编号,工号,姓名,员工属地,部门编码,部门全称
                from def_user
                where 有效标识="1" and 员工属地="%s" and 工号="%s" and 密码="%s"', 
                $company_id, $user_workid, $pswd);
        }

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
            // 存入session
            $session_arr = [];
            $session_arr['company_id'] = $company_id;
            $session_arr['user_id'] = $row->员工编号;
            $session_arr['user_workid'] = $row->工号;
            $session_arr['user_name'] = $row->姓名;
            $session_arr['user_pswd'] = $pswd;
            $session_arr['user_location'] = $row->员工属地;
            $session_arr['user_dept_code'] = $row->部门编码;
            $session_arr['user_dept_name'] = $row->部门全称;

            $session = \Config\Services::session();
            $session->set($session_arr);

            $model->sql_log('登录成功','',sprintf('属地=`%s`',$company_id));
            exit('1');
        }
    }
}