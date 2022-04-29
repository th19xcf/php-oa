<?php
/* v1.1.4.1.202204292330, from home */

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
        $path = APPPATH;
        $path = ROOTPATH;
        $path = WRITEPATH;
        //$spreadsheet = new Spreadsheet();

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
        $file =  isset($_FILES['upfiles']) ? $_FILES['upfiles'] : '';
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

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(TRUE);
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
                    赋值类型,对象,导入类型,系统变量
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

        $model->add($tmp_table_name, $sheet_data, $fld_arr);

        //数据校验

        // 插入正式表
        $sql = sprintf('select 表名 from def_import_config
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
                赋值类型,对象,导入类型,
                replace(系统变量," ","") as 系统变量
            from def_import_column
            where 导入模块="%s"', $import);

        $query = $model->select($sql);
        $results = $query->getResult();

        $tmp_fld_arr = [];
        $dest_fld_arr = [];

        foreach ($results as $row)
        {
            array_push($dest_fld_arr, $row->字段名);
            switch ($row->系统变量)
            {
                case '':
                    array_push($tmp_fld_arr, $row->字段名);
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
        }

        $tmp_fld_str = implode(',', $tmp_fld_arr);
        $dest_fld_str = implode(',', $dest_fld_arr);
        $sql_insert = sprintf('insert into %s (%s) select %s from %s', $dest_table_name, $dest_fld_str, $tmp_fld_str, $tmp_table_name);
        $rc = $model->exec($sql_insert);

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