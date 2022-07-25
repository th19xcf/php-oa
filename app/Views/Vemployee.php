<!-- v1.1.2.1.202207260025, from home -->
<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8'>
    <title>入职人员管理</title>

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
            width: 46%;
            height: 510px;
            margin: 8px;
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
        main_tb.data.add({id:'修改人员信息', type:'button', value:'修改人员信息'});
        //main_tb.data.add({id:'新增人员信息', type:'button', value:'新增人员信息'});
        main_tb.data.add({type:'separator'});
        main_tb.data.add({id:'提交', type:'button', value:'提交'});
        main_tb.data.add({type:'spacer'});
        main_tb.data.add({id:'导入', type:'button', value:'导入'});

        $$('info_box').style.height = document.documentElement.clientHeight * 0.035 + 'px';
        $$('info_box').innerHTML = '&nbsp&nbsp选定人员:';

        var csr_str = '';
        var csr_guid = [];
        var submit_type = '';

        var tree_obj = JSON.parse('<?php echo $tree_json; ?>');
        var tree = new dhx.Tree('tree_box', {checkbox: true});
        tree.data.parse(tree_obj);

        //grid视图
        var grid_obj = JSON.parse('<?php echo $grid_json; ?>');
        const grid_options = 
        {
            columnDefs: 
            [
                {field:'表项', editable:false},
                {field:'值', width:300, cellEditorSelector:cellEditorSelector}
            ],
            defaultColDef: 
            {
                resizable: true,
                editable: (params) =>
                {
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

        // 工具栏点击
        main_tb.events.on('click', function(id, e)
        {
            switch (id)
            {
                case '刷新':
                    window.location.reload();
                    break;
                case '修改人员信息':
                    submit_type = 'upkeep';
                    upkeep();
                    break;
                case '新增人员信息':
                    submit_type = 'insert';
                    //insert();
                    break;
                case '提交':
                    if (submit_type == 'upkeep')
                    {
                        upkeep_submit();
                    }
                    else if (submit_type == 'insert')
                    {
                        //insert_submit();
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
            var item = id.split('^');

            dhx.ajax.post('<?php base_url(); ?>/Employee/ajax/<?php echo $func_id; ?>', id).then(function (data)
            {
                grid_obj = JSON.parse(data);
                grid_options.api.setRowData(grid_obj);
            }).catch(function (err)
            {
                console.log('err=', err);
                alert('失败, ' + " " + err.statusText);
                tree.paint();
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
                if (item[0] != 'EE')
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
            switch (params.data.表项)
            {
                case '岗位名称':
                    return {
                        component: 'agSelectCellEditor',
                        params: {
                            values: ['','客服代表','班组长']
                        },
                    };
                    case '岗位类型':
                    return {
                        component: 'agSelectCellEditor',
                        params: {
                            values: ['','按量结算','按席结算','部分结算','无结算']
                        },
                    };
                case '员工状态':
                    return {
                        component: 'agSelectCellEditor',
                        params: {
                            values: ['在职','离职']
                        },
                    };
                case '生效日期':
                case '离职日期':
                    return {
                        component: 'datePicker',
                    };
            }
        }

        //其他函数
        function upkeep()
        {
            rowData = 
            [
                {'表项':'属性', '值':'输入新值'},
                {'表项':'生效日期', '值':''},
                {'表项':'工号1', '值':''},
                {'表项':'岗位名称', '值':''},
                {'表项':'岗位类型', '值':''},
                {'表项':'部门名称', '值':''},
                {'表项':'班组', '值':''},
                {'表项':'员工状态', '值':''},
                {'表项':'离职日期', '值':''},
                {'表项':'离职原因', '值':''},
            ];

            grid_options.api.setRowData(rowData);
        }

        function upkeep_submit(id)
        {
            if (csr_guid == false)
            {
                alert('请选择相关人员');
                return;
            }

            var ajax = 0;

            grid_options.api.stopEditing();
            grid_options.api.forEachNode((rowNode, index) =>
            {
                if (rowNode.data['表项'] == '属性' && rowNode.data['值'] != '输入新值')
                {
                    alert('请点选修改人员信息选项进行相关操作');
                    ajax = -1;
                }
            });

            if (ajax == -1)
            {
                return;
            }

            var arg_obj = {};
            arg_obj['操作'] = '更新记录';
            arg_obj['人员'] = csr_guid;

            grid_options.api.forEachNode((rowNode, index) =>
            {
                if (rowNode.data['表项'] != '属性')
                {
                    arg_obj[rowNode.data['表项']] = rowNode.data['值'];
                    if (rowNode.data['值'] != '' && rowNode.data['表项'] != '生效日期')
                    {
                        ajax = 1;
                    }
                }
            });

            if (ajax == 0)
            {
                alert('请输入要更改的信息');
                return;
            }

            if (arg_obj['生效日期'] == '' && arg_obj['员工状态'] == '')
            {
                alert('请填写生效日期');
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

            dhx.ajax.post('<?php base_url(); ?>/Employee/upkeep/<?php echo $func_id; ?>', arg_obj).then(function (data)
            {
                alert('修改成功');
                window.location.reload();
            }).catch(function (err)
            {
                alert('修改失败, ' + " " + err.statusText);
            });
        }

    </script>

</body>
</html>