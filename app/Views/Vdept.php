<!-- v2.4.2.1.202308272305, from home -->
<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8'>
    <title>部门维护</title>

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
        main_tb.data.add({id:'新增下级部门', type:'button', value:'新增下级部门'});
        main_tb.data.add({id:'修改部门信息', type:'button', value:'修改部门信息'});
        main_tb.data.add({id:'提交', type:'button', value:'提交'});
        main_tb.data.add({type:'separator'});
        main_tb.data.add({id:'删除部门', type:'button', value:'删除部门'});

        $$('info_box').style.height = document.documentElement.clientHeight * 0.035 + 'px';
        $$('info_box').innerHTML = '&nbsp&nbsp选定部门:';

        var dept_str = '';
        var guid_selected = [];
        var submit_type = '';
        var editable = false;
        var button = '';

        var tree_obj = JSON.parse('<?php echo $dept_json; ?>');
        var tree = new dhx.Tree('tree_box');
        tree.data.parse(tree_obj);

        var tree_expand_obj = JSON.parse('<?php echo $tree_expand_json; ?>');
        for (var ii in tree_expand_obj)
        {
            tree.expand(tree_expand_obj[ii]);
        }

        //grid视图
        var value_obj = [];

        const grid_options = 
        {
            columnDefs: 
            [
                {field:'表项', width:150, editable:false},
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
                        case '部门编码':
                        case '本级部门名称':
                        case '本级部门编码':
                        case '本级部门名称':
                        case '本级部门级别':
                        case '下级部门编码':
                        case '下级部门级别':
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
            //rowData: grid_obj,
        };

        new agGrid.Grid($$('grid_box'), grid_options);

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
                case '修改部门信息':
                    button = '修改部门信息';

                    var rowNode = grid_options.api.getRowNode(0);
                    rowNode.setDataValue('值', '修改部门信息');
                    submit_type = 'upkeep';
                    editable = true;
                    break;
                case '新增下级部门':
                    button = '新增下级部门';

                    submit_type = 'insert';
                    editable = true;
                    insert(guid_selected[0]);
                    break;
                case '提交':
                    if (submit_type == 'upkeep')
                    {
                        upkeep_submit();
                    }
                    else if (submit_type == 'insert')
                    {
                        insert_submit();
                    }
                    break;
                case '删除部门':
                    submit_type = 'delete';
                    editable = false;
                    delete_row();
                    break;
                default:
                    alert('功能正在开发中...');
                    break;
            }
        });

        //tree event
        tree.events.on('itemClick', function(id, e)
        {
            var arg_obj = {};
            arg_obj['操作'] = '查询部门信息';
            var item = id.split('^');
            arg_obj['id'] = item[1];

            dept_str = item[3];

            guid_selected = [];
            guid_selected.push(item[1]);

            dhx.ajax.post('<?php base_url(); ?>/dept/ajax/<?php echo $func_id; ?>', arg_obj).then(function (data)
            {
                value_obj = JSON.parse(data);
                grid_obj = JSON.parse(data);
                grid_options.api.setRowData(grid_obj);
                editable = false;
                button = '查询部门信息';
                var item = id.split('^');

                $$('info_box').innerHTML = '&nbsp&nbsp选定部门 : ' + item[3];
            }).catch(function (err)
            {
                alert('`查询部门信息`失败, ' + " " + err.statusText);
            });
        });

        tree.events.on('afterExpand', function(id) 
        {
            tree_toggle(id, '展开');
        });

        tree.events.on('afterCollapse', function(id) 
        {
            tree_toggle(id, '收缩');
        });

        function tree_toggle(id, state) 
        {
            arg_obj = {};
            arg_obj['操作'] = '展开';
            arg_obj['id_arr'] = [];

            if (state == '展开')
            {
                arg_obj['id_arr'].push(id);
            }

            tree.data.eachParent(id, item => 
            {
                arg_obj['id_arr'].push(item.id);
            });

            dhx.ajax.post('<?php base_url(); ?>/dept/ajax/<?php echo $func_id; ?>', arg_obj).then(function (data)
            {
            }).catch(function (err)
            {
            });
        }

        //grid event
        function cellEditorSelector(params)
        {
            switch (params.data.表项)
            {
                case '属地':
                    return {
                        component: 'agSelectCellEditor',
                        params: {
                            values: ['','北京总公司','河北分公司','四川分公司']
                        },
                    };
                case '下级部门':
                    return {
                        component: 'agSelectCellEditor',
                        params: {
                            values: ['','有','无']
                        },
                    };
                case '生效日期':
                case '记录开始日期':
                case '记录结束日期':
                    return {
                        component: 'datePicker',
                    };
            }
        }

        // 修改部门信息
        function upkeep_submit(id)
        {
            var ajax = 0;

            grid_options.api.stopEditing();
            grid_options.api.forEachNode((rowNode, index) =>
            {
                if (rowNode.data['表项'] == '属性' && rowNode.data['值'] != '修改部门信息')
                {
                    alert('请点选`修改部门信息`选项进行相关操作');
                    ajax = -1;
                }

                // 校验必填项
                if (rowNode.data['表项'] == '生效日期' && rowNode.data['值'] == '')
                {
                    alert('`生效日期`为必填项,不能为空');
                    ajax = -1;
                }
            });

            if (ajax == -1)
            {
                return;
            }

            var arg_obj = {};
            arg_obj['操作'] = '修改部门信息';
            arg_obj['部门'] = guid_selected;

            grid_options.api.forEachNode((rowNode, index) =>
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
                            if (rowNode.data['表项'] != '生效日期') //其他字段有更新
                            {
                                ajax = 1;
                            }
                        }

                        break;
                    }
                }
            });

            if (ajax == 0)
            {
                alert('请输入要更改的信息');
                return;
            }

            dhx.ajax.post('<?php base_url(); ?>/dept/upkeep/<?php echo $func_id; ?>', arg_obj).then(function (data)
            {
                alert(data);
                window.location.reload();
            }).catch(function (err)
            {
                alert('`修改部门信息`失败, ' + " " + err.statusText);
            });
        }

        // 用ajax先获得部门相关信息
        function insert(id)
        {
            var arg_obj = {};
            arg_obj['操作'] = '新增下级部门';
            arg_obj['id'] = id;

            dhx.ajax.post('<?php base_url(); ?>/dept/ajax/<?php echo $func_id; ?>', arg_obj).then(function (data)
            {
                value_obj = JSON.parse(data);
                grid_obj = JSON.parse(data);
                grid_options.api.setRowData(grid_obj);
                editable = true;
                button = '新增下级部门';
            }).catch(function (err)
            {
                alert('`新增下级部门`失败, ' + " " + err.statusText);
            });
        }

        // 新增记录提交
        function insert_submit(id)
        {
            var ajax = 0;

            grid_options.api.stopEditing();
            grid_options.api.forEachNode((rowNode, index) =>
            {
                if (rowNode.data['表项'] == '属性' && rowNode.data['值'] != '新增下级部门')
                {
                    alert('请点选`新增下级部门`选项进行相关操作');
                    ajax = -1;
                }
                // 校验必填项
                if (rowNode.data['表项'] == '生效日期' && rowNode.data['值'] == '')
                {
                    alert('`生效日期`为必填项,不能为空');
                    ajax = -1;
                }
                if (rowNode.data['表项'] == '下级部门名称' && rowNode.data['值'] == '')
                {
                    alert('`下级部门名称`为必填项,不能为空');
                    ajax = -1;
                }
                if (rowNode.data['表项'] == '下级部门负责人' && rowNode.data['值'] == '')
                {
                    alert('`下级部门负责人`为必填项,不能为空');
                    ajax = -1;
                }
            });

            if (ajax == -1)
            {
                return;
            }

            var arg_obj = {};
            arg_obj['操作'] = '新增下级部门';

            grid_options.api.forEachNode((rowNode, index) =>
            {
                if (rowNode.data['表项'] != '属性')
                {
                    arg_obj[rowNode.data['表项']] = rowNode.data['值'];
                    if (rowNode.data['值'] != '')
                    {
                        ajax = 1;
                    }
                }
            });

            if (ajax == 0)
            {
                alert('请输入新增部门的相关信息');
                return;
            }

            dhx.ajax.post('<?php base_url(); ?>/dept/insert/<?php echo $func_id; ?>', arg_obj).then(function (data)
            {
                alert(data);
                window.location.reload();
            }).catch(function (err)
            {
                alert('`新增下级部门`失败, ' + " " + err.statusText);
            });
        }

        function delete_row(id)
        {
            var ajax = 0;
            var dept_name = '';

            grid_options.api.stopEditing();
            grid_options.api.forEachNode((rowNode, index) =>
            {
                // 校验
                if (rowNode.data['表项'] == '属性' && rowNode.data['值'] != '查询部门信息')
                {
                    alert('`属性`不为`查询部门信息`,不能进行`删除部门`操作');
                    ajax = -1;
                }
                if (rowNode.data['表项'] == '下级部门' && rowNode.data['值'] == '有')
                {
                    alert('有下级部门,不能删除');
                    ajax = -1;
                }
                if (rowNode.data['表项'] == '部门名称')
                {
                    dept_name = rowNode.data['值'];
                }
            });

            if (ajax == -1)
            {
                return;
            }

            if (confirm('请确认是否删除部门: '+dept_name) == false)
            {
                return;
            }

            var arg_obj = {};
            arg_obj['操作'] = '删除部门';
            arg_obj['部门'] = guid_selected;

            dhx.ajax.post('<?php base_url(); ?>/dept/delete_row/<?php echo $func_id; ?>', arg_obj).then(function (data)
            {
                alert(data);
                window.location.reload();
            }).catch(function (err)
            {
                alert('删除部门失败, ' + " " + err.statusText);
            });
        }

    </script>

</body>
</html>
