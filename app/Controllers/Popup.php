<?php
/* v1.2.1.1.202405070930, from office */

namespace App\Controllers;
use \CodeIgniter\Controller;
use App\Models\Mcommon;

class Popup extends Controller
{
    public function __construct()
    {
        helper(['form', 'url']);
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 运营部门校验
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function dept_verify($menu_id='')
    {
        $arg = $this->request->getJSON(true);
        $model = new Mcommon();
        $sql = sprintf('
            select 部门编码,部门名称,一级全称,部门级别,上级部门编码
            from view_dept
            where 部门级别="%s"
                and 一级部门名称="%s"
                and 二级部门名称="%s"
                and 三级部门名称="%s"
                and 四级部门名称="%s"
                and 五级部门名称="%s"
                and 六级部门名称="%s"
                and 七级部门名称="%s"',
            $arg['部门级别'],
            $arg['一级部门'], $arg['二级部门'], $arg['三级部门'], 
            $arg['四级部门'], $arg['五级部门'], $arg['六级部门'],
            $arg['七级部门']);

        $rows = $model->select($sql)->getResultArray();
        exit(sprintf('%s^%s', $rows[0]['部门编码'], $rows[0]['一级全称']));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 预算部门校验
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function budget_verify($menu_id='')
    {
        $arg = $this->request->getJSON(true);
        $model = new Mcommon();
        $sql = sprintf('
            select "" as 部门编码,统计部门全称
            from 中心_预算_部门
            where 统计部门级别="%s"
                and 一级统计部门="%s"
                and 二级统计部门="%s"
                and 三级统计部门="%s"
                and 四级统计部门="%s"
                and 五级统计部门="%s"
                and 六级统计部门="%s"
                and 七级统计部门="%s"',
            $arg['部门级别'],
            $arg['一级部门'], $arg['二级部门'], $arg['三级部门'], 
            $arg['四级部门'], $arg['五级部门'], $arg['六级部门'],
            $arg['七级部门']);

        $rows = $model->select($sql)->getResultArray();
        exit(sprintf('%s^%s', $rows[0]['部门编码'], $rows[0]['统计部门全称']));
    }

    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    // 科目校验
    //+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=
    public function fd_verify($menu_id='')
    {
        $arg = $this->request->getJSON(true);
        $model = new Mcommon();
        $sql = sprintf('
            select 科目编码,科目名称,科目全称,科目级别,上级科目编码
            from view_中心_预算_科目
            where 科目级别="%s"
                and 一级科目名称="%s"
                and 二级科目名称="%s"
                and 三级科目名称="%s"
                and 四级科目名称="%s"
                and 五级科目名称="%s"',
            $arg['科目级别'],
            $arg['一级科目'], $arg['二级科目'], $arg['三级科目'], 
            $arg['四级科目'], $arg['五级科目']);

        $rows = $model->select($sql)->getResultArray();
        exit(sprintf('%s^%s', $rows[0]['科目编码'], $rows[0]['科目全称']));
    }
}