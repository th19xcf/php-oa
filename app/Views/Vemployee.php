<!-- v2.3.1.1.202301042145, from home -->
<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8'>
    <title>在职人员维护</title>

    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/dhtmlx/codebase/suite.css'>
    <script src='<?php base_url(); ?>/dhtmlx/codebase/suite.js'></script>

    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/ag-grid/dist/styles/ag-grid.css'>
    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/ag-grid/dist/styles/ag-theme-alpine.css'>
    <script src='<?php base_url(); ?>/ag-grid/dist/ag-grid-locale-cn.js'></script>
    <script src='<?php base_url(); ?>/ag-grid/dist/ag-grid-community.noStyle.js'></script>

    <script src='<?php base_url(); ?>/assets/js/datepicker_brower.js'></script>

    <style type='text/css'>
        div.float_box
        {
            width: 47%;
            height: 510px;
            margin: 4px;
            background-color: #f9f9f9;
            border: 1px solid #D0D0D0;
            box-sizing: border-box;
            float: left;
        }
    </style>
</head>

<body>
    <div id='main_tb' ></div>
    <div id='info_box' style='width:100%; height:10px; margin-bottom:3px; background-color: lightblue;'></div>
    <div class='float_box'>
        <div id='tree_box' style='height:100%;'></div>
    </div>

    <div class='float_box'>
        <div id='grid_box' class='ag-theme-alpine' style='width:100%; height:100%; background-color:lightblue;'></div>
    </div>

    <script type='text/javascript' charset='utf-8'>
        function $$(id)
        {
            return document.getElementById(id);
        }

        // tree视图
        var main_tb = new dhx.Toolbar('main_tb', {css:'toobar-class'});
        main_tb.data.add({id:'刷新', type:'button', value:'刷新'});
        main_tb.data.add({type:'separator'});
        main_tb.data.add({id:'修改个人信息 (单选)', type:'button', value:'修改个人信息 (单选)'});
        main_tb.data.add({id:'修改共性信息 (多选)', type:'button', value:'修改共性信息 (多选)'});
        main_tb.data.add({id:'修改提交', type:'button', value:'修改提交'});
        main_tb.data.add({type:'separator'});
        main_tb.data.add({id:'删除', type:'button', value:'删除'});
        main_tb.data.add({type:'spacer'});
        main_tb.data.add({id:'导入', type:'button', value:'导入'});

        $$('info_box').style.height = document.documentElement.clientHeight * 0.035 + 'px';
        $$('info_box').innerHTML = '&nbsp&nbsp选定人员:';

        var csr_str = '';
        var csr_guid = [];
        var csr_guid_query = '';
        var submit_type = '';
        var editable = false;
        var button = '';

        var tree_obj = JSON.parse('<?php echo $tree_json; ?>');
        var tree = new dhx.Tree('tree_box', {checkbox: true});
        tree.data.parse(tree_obj);

        //grid视图
        var grid_obj = JSON.parse('<?php echo $grid_json; ?>');
        var value_obj = JSON.parse('<?php echo $grid_json; ?>');

        const grid_options = 
        {
            columnDefs: 
            [
                {field:'表项', width:130, editable:false},
                {field:'值', width:280, cellEditorSelector:cellEditorSelector}
            ],
            defaultColDef: 
            {
                resizable: true,
                editable: (params) =>
                {
                    if (editable == false) return false;

                    // 根据配置判断是否可以修改
                    switch (params.data.表项)
                    {
                        case '属性':
                            return false;
                        default:
                            return true;
                    }
                },
            },
            components:
            {
                datePicker: get_date_picker(),
            },
            rowData: grid_obj,
        };

        new agGrid.Grid($$('grid_box'), grid_options);

        /*
        grid_options.onCellValueChanged = cellchanaged;
        function cellchanaged(params)
        {
          // your code here
          console.log('cell changed', params);
        };
        */

        // 工具栏点击
        main_tb.events.on('click', function(id, e)
        {
            switch (id)
            {
                case '刷新':
                    window.location.reload();
                    editable = false;
                    button = '';
                    break;
                case '修改个人信息 (单选)':
                    if (button != '查询人员信息')
                    {
                        alert('查询人员信息下, 才能修改');
                        return;
                    }
                    if (csr_guid.length == 0)
                    {
                        alert('请选择相关人员');
                        return;
                    }
                    else if (csr_guid.length > 1)
                    {
                        alert('只能选择一个人员');
                        return;
                    }

                    if (csr_guid[0] != csr_guid_query)
                    {
                        alert('选择人员和查询人员不符, 请重新选择')
                    }

                    var rowNode = grid_options.api.getRowNode(0);
                    rowNode.setDataValue('值', '修改个人信息 (单选)');
                    submit_type = 'upkeep_single';
                    editable = true;
                    break;
                case '修改共性信息 (多选)':
                    rowData = 
                        [
                            {'表项':'属性', '值':'修改共性信息 (多选)'},
                            {'表项':'生效日期', '值':''},
                            {'表项':'部门名称', '值':''},
                            {'表项':'班组', '值':''},
                            {'表项':'员工状态', '值':''},
                            {'表项':'一阶段日期', '值':''},
                            {'表项':'二阶段日期', '值':''},
                            {'表项':'离职日期', '值':''},
                            {'表项':'离职原因', '值':''},
                        ];

                    grid_options.api.setRowData(rowData);

                    if (csr_guid.length == 0)
                    {
                        alert('请选择相关人员');
                        return;
                    }

                    var rowNode = grid_options.api.getRowNode(0);
                    rowNode.setDataValue('值', '修改共性信息 (多选)');
                    submit_type = 'upkeep_multi';
                    editable = true;
                    break;
                case '修改提交':
                    if (submit_type != '')
                    {
                        upkeep_submit();
                    }
                    else
                    {
                        alert('请先选择功能按钮进行操作');
                    }
                    break;
                case '删除':
                    if (csr_guid.length == 0)
                    {
                        alert('请选择相关人员');
                        return;
                    }
                    if (confirm('请确认是否进行删除操作?') == true)
                    {
                        delete_submit();
                    }
                    break;
                case '导入':
                    parent.window.goto('<?php echo $import_func_id; ?>','导入-'+'<?php echo $import_func_name; ?>','Upload/init/<?php echo $import_func_id; ?>');
                    break;
            }
        });

        //tree event
        tree.events.on('itemClick', function(id, e)
        {
            dhx.ajax.post('<?php base_url(); ?>/employee/ajax/<?php echo $func_id; ?>', id).then(function (data)
            {
                value_obj = JSON.parse(data);  //原记录
                grid_obj = JSON.parse(data);
                grid_options.api.setRowData(grid_obj);
                editable = false;
                button = grid_obj[0]['值'];
                var item = id.split('^');
                csr_guid_query = item[1];
            }).catch(function (err)
            {
                console.log('err=', err);
                alert('失败, ' + " " + err.statusText);
            });
        });

        tree.events.on('afterCheck', function (index, id, value)
        {
            csr_str = '';
            csr_guid = [];

            item = [];
            selected_arr = tree.getChecked();
            for (var ii in selected_arr)
            {
                item = selected_arr[ii].split('^');
                if (item[0] != '人员')
                {
                    continue;
                }
                if (csr_str!='') csr_str = csr_str + ',';
                csr_str = csr_str + item[2];
                csr_guid.push(item[1]);
            }
            $$('info_box').innerHTML = '&nbsp&nbsp选定人员:' + csr_str;
        });

        //grid event
        function cellEditorSelector(params)
        {
            var col_name = params.data.列名;

            switch (params.data.表项)
            {
                case '员工状态':
                    return {
                        component: 'agSelectCellEditor',
                        params: {
                            values: ['在职','离职']
                        },
                    };
                case '属地':
                    return {
                        component: 'agSelectCellEditor',
                        params: {
                            values: ['','北京总公司','河北分公司','四川分公司']
                        },
                    };
                case '住宿':
                    return {
                        component: 'agSelectCellEditor',
                        params: {
                            values: ['','是','否']
                        },
                    };
                case '生效日期':
                case '一阶段日期':
                case '二阶段日期':
                case '离职日期':
                    return {
                        component: 'datePicker',
                    };
            }
        }

        // 更新人员信息
        function upkeep_submit(id)
        {
            var ajax = 0;

            grid_options.api.stopEditing();
            grid_options.api.forEachNode((rowNode, index) =>
            {
                if (rowNode.data['表项'] == '属性')
                {
                    if (rowNode.data['值'] != '修改个人信息 (单选)' && rowNode.data['值'] != '修改共性信息 (多选)')
                    {
                        alert('请点选修改按钮,进行相关操作');
                        ajax = -1;
                    }
                }
            });

            if (ajax == -1)
            {
                return;
            }

            var arg_obj = {};
            arg_obj['操作'] = '修改记录';
            arg_obj['人员'] = csr_guid;

            grid_options.api.forEachNode((rowNode, index) =>
            {
                // 单选
                if (submit_type == 'upkeep_single')
                {
                    if (rowNode.data['表项'] != '属性')
                    {
                        for (var jj in value_obj)
                        {
                            if (rowNode.data['表项'] != value_obj[jj]['表项']) continue;

                            arg_obj[rowNode.data['表项']] = {};
                            arg_obj[rowNode.data['表项']]['值'] = rowNode.data['值'];
                            arg_obj[rowNode.data['表项']]['更改标识'] = '0';

                            if (rowNode.data['值'] != value_obj[jj]['值']) //值有更新
                            {
                                arg_obj[rowNode.data['表项']]['更改标识'] = '1';
                                ajax = 1;
                            }

                            break;
                        }
                    }
                }
                else if (submit_type = 'upkeep_multi')
                {
                    if (rowNode.data['表项'] != '属性' && rowNode.data['值'] != '')
                    {
                        arg_obj[rowNode.data['表项']] = {};
                        arg_obj[rowNode.data['表项']]['值'] = rowNode.data['值'];
                        arg_obj[rowNode.data['表项']]['更改标识'] = '1';
                        ajax = 1;
                    }
                }
            });

            if (ajax == 0)
            {
                alert('没有更新的信息');
                return;
            }

            if (arg_obj['生效日期'] == '')
            {
                alert('需要填写生效日期');
                return;
            }

            if (arg_obj['员工状态'] == '在职')
            {
                if (arg_obj['离职日期'] != '')
                {
                    alert('在职,不要填写离职日期');
                    return;
                }
                if (arg_obj['离职原因'] != '')
                {
                    alert('在职,不要填写离职原因');
                    return;
                }
            }

            if (arg_obj['员工状态'] == '离职')
            {
                if (arg_obj['离职日期'] == '')
                {
                    alert('离职,请填写离职日期');
                    return;
                }
                if (arg_obj['离职原因'] == '')
                {
                    alert('离职,请填写离职原因');
                    return;
                }
            }

            dhx.ajax.post('<?php base_url(); ?>/employee/upkeep/<?php echo $func_id; ?>', arg_obj).then(function (data)
            {
                alert('修改成功');
                submit_type = '';
                window.location.reload();
            }).catch(function (err)
            {
                alert('修改失败, ' + " " + err.statusText);
            });
        }

        function delete_submit(id)
        {
            var ajax = 0;

            var arg_obj = {};
            arg_obj['操作'] = '删除记录';
            arg_obj['人员'] = csr_guid;

            dhx.ajax.post('<?php base_url(); ?>/employee/delete_row/<?php echo $func_id; ?>', arg_obj).then(function (data)
            {
                alert('删除成功');
                window.location.reload();
            }).catch(function (err)
            {
                alert('删除失败, ' + " " + err.statusText);
            });
        }


    </script>

</body>
</html>
