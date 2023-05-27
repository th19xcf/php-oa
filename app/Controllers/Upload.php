<?php
/* v2.6.1.1.202305270950, from home */

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
            select 功能编码,导入模块,导入条件,
                表单变量,滤重字段,模板文件,表头行,数据行
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
        $user_location = $session->get('user_location');
        $user_location_str = $session->get('user_location_str');
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

        #$sheet_data = $sheet->toArray(true, true, true, true, true);
        $sheet_data = $sheet->toArray($nullValue='');

        if(count($sheet_data) <= 1)
        {
            $this->json_data(400, '导入的表是空表,请重试！', 0);
            return;
        }

        //数据库操作
        $tmp_table_name = sprintf('tmp_%s_%s_%s_%s', $menu_id, $menu_1, $menu_2, $user_workid);

        $sql = sprintf('
            select 列名,字段名,字段类型,字段长度,
                校验信息,校验类型,对象,系统变量,顺序
            from def_import_column
            where 导入模块="%s" and 系统变量="" and 顺序>0
            order by 顺序', $import);

        $model = new Mcommon();
        $query = $model->select($sql);
        $results = $query->getResult();

        $col_arr = [];
        $fld_arr = [];
        $fld_ceate_str = '';

        foreach ($results as $row)
        {
            if (in_array($row->列名, $sheet_data[0]) == false)
            {
                $this->json_data(400, sprintf('导入失败,没有要求的字段"%s"',$row->列名), 0);
                return;
            }

            array_push($col_arr, $row->列名);
            array_push($fld_arr, $row->字段名);

            if ($fld_ceate_str != '') $fld_ceate_str = $fld_ceate_str . ',';
            $fld_ceate_str = sprintf('%s %s varchar(%s) not null default ""', $fld_ceate_str, $row->字段名, $row->字段长度);
        }

        $fact_data = [];
        $ii = -1;
        foreach ($sheet_data as $data)
        {
            $ii ++;
            if ($ii == 0) continue;

            //判断是否所有数据都为空
            $empty = true;
            foreach ($data as $item)
            {
                if ($item != '')
                {
                    $empty = false;
                    break;
                }
            }

            if ($empty) continue;

            $arr = [];
            $data = array_combine($sheet_data[0], $data);  //修改键名
            foreach ($col_arr as $col)
            {
                $arr[$col] = $data[$col];
            }
            array_push($fact_data, $arr);
        }

        $sql = sprintf('drop table if exists %s;', $tmp_table_name);
        $rc = $model->exec($sql);

        $sql = sprintf('create table %s (%s);', 
            $tmp_table_name, $fld_ceate_str);
        $rc = $model->exec($sql);

        $rc = $model->add_by_trans($tmp_table_name, $fact_data, $col_arr, $fld_arr);
        if ($rc == -1)
        {
            $this->json_data(400, '导入失败,事务执行错误,请重试！', 0);
            return;
        }

        //数据校验
        foreach ($results as $row)
        {
            $sql = '';
            if (strpos($row->校验类型,'固定值') !== false)
            {
                $sql = sprintf('
                    select 
                        t1.字段名 as 字段名,
                        t1.字段值 as 字段值,
                        ifnull(t2.对象值,"") as 对象值
                    from
                    (
                        select "%s" as 字段名, %s as 字段值
                        from %s
                        group by 字段值
                    ) as t1
                    left join
                    (
                        select 对象名称,对象值
                        from def_object
                        where 对象名称="%s"
                            and (属地="" or 属地 in (%s))
                    ) as t2 on t1.字段值=t2.对象值
                    where t2.对象值 is null',
                    $row->字段名, $row->字段名, $tmp_table_name,
                    $row->对象, $user_location_str);

                $errs = $model->select($sql)->getResultArray();
                if (count($errs) != 0)
                {
                    $err_arr = [];
                    foreach ($errs as $err)
                    {
                        array_push($err_arr, $err['字段值']);
                    }
                    $this->json_data(400, sprintf('导入失败,列"%s"有不符合固定值的记录 {%s}', $row->列名, implode(',', $err_arr)), 0);
                    return;
                }
            }

            if (strpos($row->校验类型,'条件') !== false)
            {
                if ($row->校验信息 == '') continue;

                $sql = sprintf('
                    select "%s" as 字段名, %s as 字段值 from %s where %s',
                    $row->列名, $row->列名, $tmp_table_name, $row->校验信息);

                $errs = $model->select($sql)->getResultArray();
                if (count($errs) != 0)
                {
                    $err_arr = [];
                    foreach ($errs as $err)
                    {
                        array_push($err_arr, $err['字段值']);
                    }
                    $this->json_data(400, sprintf('导入失败,列"%s"有不符合条件的记录 {%s}', $row->列名, implode(',', $err_arr)), 0);
                    return;
                }
            }

            if (strpos($row->校验类型,'日期') !== false)
            {
                $sql = sprintf('
                    select "%s" as 字段名, %s as 字段值 from %s',
                    $row->列名, $row->列名, $tmp_table_name);

                $dates = $model->select($sql)->getResult();
                foreach ($dates as $date)
                {
                    //只判断非空值
                    if ($date->字段值 == '') continue;
                    //匹配日期格式,YYYY-mm-dd
                    $parts = [];
                    if (preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/",$date->字段值,$parts))
                    {
                        //检测是否为日期
                        if(checkdate($parts[2],$parts[3],$parts[1]) == false)
                        {
                            $this->json_data(400, sprintf('导入失败,列"%s"有不符合的记录{%s},必须为YYYY-mm-dd (如2023-01-02) 格式', $row->列名,$date->字段值), 0);
                            return;
                        }
                    }
                    else
                    {
                        $this->json_data(400, sprintf('导入失败,列"%s"有不符合的记录{%s},必须为YYYY-mm-dd (如2023-01-02) 格式', $row->列名,$date->字段值), 0);
                        return;
                    }
                }
            }
        }

        //是否有重复记录
        $sql = sprintf('
            select 表名,导入条件,表单变量,滤重字段
            from def_import_config
            where 导入模块="%s"', $import);

        $query = $model->select($sql);
        $results = $query->getResult();

        if ($results[0]->滤重字段 != '')
        {
            $sql = sprintf('
                select %s from %s
                where concat(%s) in ( select concat(%s) from %s )', 
                $results[0]->滤重字段, $results[0]->表名,
                $results[0]->滤重字段, $results[0]->滤重字段, $tmp_table_name);

            $errs = $model->select($sql)->getResultArray();

            if (count($errs) != 0)
            {
                $err_arr = [];
                foreach ($errs as $err)
                {
                    $str = '';
                    foreach ($err as $item)
                    {
                        if ($str!='') $str = $str . '^';
                        $str = $str . $item;
                    }
                    array_push($err_arr, $str);
                }
                $this->json_data(400, sprintf('导入失败,滤重列"%s"有重复记录 {%s}', $results[0]->滤重字段, implode(',', $err_arr)), 0);
                return;
            }
        }

        // 插入正式表
        $sql = sprintf('
            select 表名,导入条件,表单变量
            from def_import_config
            where 导入模块="%s"', $import);

        $query = $model->select($sql);
        $results = $query->getResult();

        $dest_table_name = '';
        $import_condition = '';
        foreach ($results as $row)
        {
            $dest_table_name = $row->表名;
            $import_condition = $row->导入条件;
            break;
        }

        $sql = sprintf('
            select 列名,查询名,字段名,字段类型,字段长度,
                校验类型,对象,
                replace(系统变量," ","") as 系统变量,
                replace(表单变量," ","") as 表单变量
            from def_import_column
            where 导入模块="%s" and 顺序>0', $import);

        $query = $model->select($sql);
        $results = $query->getResult();

        $tmp_fld_arr = [];
        $dest_fld_arr = [];

        foreach ($results as $row)
        {
            array_push($dest_fld_arr, $row->字段名);

            if ($row->系统变量=='' &&  $row->表单变量=='')
            {
                if ($row->查询名 != '')
                {
                    array_push($tmp_fld_arr, sprintf('%s as %s', $row->查询名, $row->字段名));
                }
                else
                {
                    array_push($tmp_fld_arr, $row->字段名);
                }
                continue;
            }
            switch ($row->系统变量)
            {
                case '':
                    break;
                case '$属地':
                    array_push($tmp_fld_arr, sprintf('"%s" as %s', $user_location, $row->字段名));
                    break;
                case '$工号':
                    array_push($tmp_fld_arr, sprintf('"%s" as %s', $user_workid, $row->字段名));
                    break;
                case '$时间戳':
                    array_push($tmp_fld_arr, sprintf('"%s" as %s', date('Y-m-d H:i:s'), $row->字段名));
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
        if ($import_condition != '')
        {
            $sql_insert = sprintf('%s where %s', $sql_insert ,$import_condition);
        }
        $rc = $model->exec($sql_insert);

        // 写日志
        $model->sql_log('导入成功', $menu_id, '');

        $this->json_data(200, '导入成功', 0);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 自定义函数
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function json_data($status=200, $msg='', $count=0)
    {
        $res = [
            'status' => $status,
            'msg' => $msg,
            'number' => $count
        ];

        echo json_encode($res);
        die;
    }
}
