<?php

/* v1.0.0.1.202110071730, from home */

namespace App\Controllers;
use \CodeIgniter\Controller;
use App\Models\Mlogin;

class Login extends Controller
{
    public function __construct()
    {
        helper(['form', 'url']);
    }

    public function index()
    {
        $Arg['NextPage'] = base_url('login/checkin');
        echo view('Vlogin.php', $Arg);
    }

    public function checkin()
    {
        #$class_id = $this->request->getPost('company_id');
        $user_id = $this->request->getPost('userid');
        $pswd = $this->request->getPost('userpwd');

        $model = new Mlogin();
        $results = $model->checkin($user_id, $pswd);
        if ($results==null)
        {
            $Arg['msg'] = '工号或密码错误, 请重新输入！';
            exit('2');
        }

        foreach ($results as $row)
        {
            $Info['员工编号'] = $row->员工编号;
            exit('1');
        }
    }
}