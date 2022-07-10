<?php
/* v1.5.1.1.202207101740, from home */

namespace App\Controllers;
use \CodeIgniter\Controller;
use App\Models\Mcommon;

require '..\vendor\autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Upload extends Controller
{
    public function __construct()
    {
        helper(['form', 'url']);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 初始页面
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function init($menu_id='')
    {
        $send = [];

        $sql = sprintf('
            select 功能编码,导入模块,表单变量,模板文件
            from def_function as t1
            left join def_import_config as t2
            on t1.查询模块=t2.导入模块
            where 功能编码="%s"', $menu_id);

        $model = new Mcommon();
        $query = $model->select($sql);
        $results = $query->getResult();

        foreach ($results as $row)
        {
            $send['work_month'] = strpos($row->表单变量, '$工作月份');
            $send['work_date'] = strpos($row->表单变量, '$工作日期');
            $send['tmpl_file'] = $row->模板文件;
        }

        // 存入session
        $session_arr = [];
        $session_arr[$menu_id.'-import'] = $row->导入模块;

        $session = \Config\Services::session();
        $session->set($session_arr);

        $send['func_id'] = $menu_id;
        $send['import_page'] = base_url('upload/import/'.$menu_id);
        $send['export_page'] = base_url('upload/import/');

        echo view('Vupload.php', $send);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 文件导入
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function import($menu_id='')
    {
        $work_month = $this->request->getPost('work_month');
        $work_date = $this->request->getPost('work_date');

        $file = isset($_FILES['upfiles']) ? $_FILES['upfiles'] : '';
        if (empty($file))
        {
            $this->json_data(204, '上传文件为空！', 0);
            return;
        }

        $session = \Config\Services::session();
        $user_workid = $session->get('user_workid');
        $menu_1 = $session->get($menu_id.'-menu_1');
        $menu_2 = $session->get($menu_id.'-menu_2');
        $import = $session->get($menu_id.'-import');

        $file_name = $file['name'];
        $ext = substr($file_name, strrpos($file_name, '.'));
        $tmp = strstr($file_name, '.', true);
        $save_path = sprintf('%s/uploads/%s', WRITEPATH, $menu_id);
        $tmp_path = $file['tmp_name'];
        $size = $file['size'];

        if (!is_dir($save_path))
        {
            if (!mkdir($save_path, 0777, true))
            {
                $this->json_data(400, '创建文件夹失败, 请联系管理员。', 0);
            }
        }

        $new_file_name = sprintf('%s\%s_%s_%s%s', $save_path, $user_workid, $menu_1, $menu_2, $ext);
        if (!move_uploaded_file($tmp_path, $new_file_name))
        {
            $this->json_data(204, '复制文件失败', 0);
        }

        if ($ext == '.xls')
        {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xls');
        }
        else if ($ext == '.xlsx')
        {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        }

        //$reader->setReadDataOnly(TRUE);
        $spreadsheet = $reader->load($new_file_name); //载入excel表格

        $sheet_count = $spreadsheet->getSheetCount();
        $sheet = $spreadsheet->getSheet(0); // 只处理第一张sheet
        //$sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow(); // 总行数
        $highestColumn = $sheet->getHighestColumn(); // 总列数

        $sheet_data = $sheet->toArray(true, true, true, true, true);

        //数据库操作
        $tmp_table_name = sprintf('tmp_%s_%s_%s_%s', $menu_id, $menu_1, $menu_2, $user_workid);

        $sql = sprintf(
                'select 列名,字段名,字段类型,字段长度,
                    校验类型,对象,导入类型,系统变量
                from def_import_column
                where 导入模块="%s" and 系统变量=""', $import);

        $model = new Mcommon();
        $query = $model->select($sql);
        $results = $query->getResult();

        $fld_arr = [];
        $fld_ceate_str = '';

        foreach ($results as $row)
        {
            array_push($fld_arr, $row->字段名);

            if ($fld_ceate_str != '') $fld_ceate_str = $fld_ceate_str . ',';
            $fld_ceate_str = sprintf('%s %s varchar(%s) not null default ""', $fld_ceate_str, $row->字段名, $row->字段长度);
        }

        $sql = sprintf('drop table if exists %s;', $tmp_table_name);
        $rc = $model->exec($sql);

        $sql = sprintf('create table %s (%s);', 
            $tmp_table_name, $fld_ceate_str);
        $rc = $model->exec($sql);

        $rc = $model->add_by_trans($tmp_table_name, $sheet_data, $fld_arr);
        if ($rc == -1)
        {
            $this->json_data(400, '导入失败,请重试！', 0);
            return;
        }

        //数据校验
        foreach ($results as $row)
        {
            switch ($row->校验类型)
            {
                case '固定值':
                    $sql = sprintf('
                        select t1.%s as 变量值,t2.对象值 as 对象值
                        from
                        (
                            select %s 
                            from %s
                            group by %s
                        ) as t1
                        left join
                        (
                            select 对象名称,对象值
                            from def_object
                            where 对象名称="%s"
                        ) as t2 on t1.%s=t2.对象值
                        where t2.对象值 is null',
                        $row->字段名, $row->字段名, $tmp_table_name, $row->字段名,
                        $row->对象, $row->字段名);

                    $errs = $model->select($sql)->getResult();

                    if (count($errs) != 0)
                    {
                        $err_arr = [];
                        foreach ($errs as $err)
                        {
                            array_push($err_arr, $err->变量值);
                        }
                        $this->json_data(400, sprintf('导入失败,列"%s"有不符合的记录 [%s]',$row->字段名, implode(',', $err_arr)), 0);
                        return;
                    }
                    break;
                default:
                    break;
            }
        }

        // 插入正式表
        $sql = sprintf('select 表名,表单变量
            from def_import_config
            where 导入模块="%s"', $import);

        $query = $model->select($sql);
        $results = $query->getResult();

        $dest_table_name = '';
        foreach ($results as $row)
        {
            $dest_table_name = $row->表名;
            break;
        }

        $sql = sprintf(
            'select 列名,字段名,字段类型,字段长度,
                校验类型,对象,导入类型,
                replace(系统变量," ","") as 系统变量,
                replace(表单变量," ","") as 表单变量
            from def_import_column
            where 导入模块="%s"', $import);

        $query = $model->select($sql);
        $results = $query->getResult();

        $tmp_fld_arr = [];
        $dest_fld_arr = [];

        foreach ($results as $row)
        {
            array_push($dest_fld_arr, $row->字段名);

            if ($row->系统变量=='' &&  $row->表单变量=='')
            {
                array_push($tmp_fld_arr, $row->字段名);
                continue;
            }
            switch ($row->系统变量)
            {
                case '':
                    break;
                case '$工号':
                    array_push($tmp_fld_arr, sprintf('"%s" as %s', $user_workid, $row->字段名));
                    break;
                case '$时间戳':
                    array_push($tmp_fld_arr, sprintf('"" as %s', $row->字段名));
                    break;
                default:
                    array_push($tmp_fld_arr, sprintf('"%s" as %s', $row->系统变量, $row->字段名));
                    break;
            }
            switch ($row->表单变量)
            {
                case '':
                    break;
                case '$工作月份':
                    array_push($tmp_fld_arr, sprintf('"%s" as %s', $work_month, $row->字段名));
                    break;
                case '$工作日期':
                    array_push($tmp_fld_arr, sprintf('"%s" as %s', $work_date, $row->字段名));
                    break;
                default:
                    array_push($tmp_fld_arr, sprintf('"%s" as %s', $row->系统变量, $row->字段名));
                    break;
            }
        }

        $tmp_fld_str = implode(',', $tmp_fld_arr);
        $dest_fld_str = implode(',', $dest_fld_arr);
        $sql_insert = sprintf('insert into %s (%s) select %s from %s', $dest_table_name, $dest_fld_str, $tmp_fld_str, $tmp_table_name);
        $rc = $model->exec($sql_insert);

        // 写日志
        $model->sql_log('导入成功',$menu_id,'');

        $this->json_data(200, '导入成功', 0);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 自定义函数
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function json_data($status=200, $msg, $count, $data =[])
    {
        $res = [
            'status' => $status,
            'msg'=>$msg,
            'number'=>$count,
            'data'=>$data
        ];

        echo json_encode($res);
        die;
    }
}