<!-- v2.7.1.1.202501021905, from home -->
<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8'>
    <title>部门维护</title>

    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/dhtmlx/codebase/suite.css'>
    <script src='<?php base_url(); ?>/dhtmlx/codebase/suite.js'></script>

    <script src='<?php base_url(); ?>/ag-grid/dist/ag-grid-locale-cn.js'></script>
    <script src='<?php base_url(); ?>/ag-grid/dist/ag-grid-community.noStyle.js'></script>
    <script src='<?php base_url(); ?>/assets/js/datepicker_brower.js'></script>

    <style type='text/css'>
        div.float_box
        {
            width: 49%;
            height: 510px;
            margin: 3px;
            background-color: #f9f9f9;
            border: 1px solid #D0D0D0;
            box-sizing: border-box;
            float: left;
        }
    </style>
</head>

<body>
    <div id='databox' style='width:100%;'>
        <div id='main_tb'></div>
        <div id='infobox' style='width:100%; height:10px; margin-bottom:4px; background-color: lightblue;'></div>
        <div id='float_div' style='width:100%;'>
            <div class='float_box'>
                <div id='tree_box' style='height:100%;'></div>
            </div>
            <div class='float_box'>
                <div id='grid_box' style='width:100%; height:100%; background-color:lightblue;'></div>
            </div>
        </div>
    </div>
    <div id='footbox' style='width:100%; height:10px; margin-top:1px; background-color: lightblue;'></div>

    <script type='text/javascript' charset='utf-8'>
        function $$(id)
        {
            return document.getElementById(id);
        }

        $$('infobox').style.height = document.documentElement.clientHeight * 0.033 + 'px';
        $$('databox').style.height = document.documentElement.clientHeight * 0.92 + 'px';
        $$('footbox').style.height = document.documentElement.clientHeight * 0.033 + 'px';

        function BudgetInfo()
        {
            this.menu_1 = '';
            this.menu_2 = '';
            this.grid_api;
            this.node_id = '';
            this.col_name = '';
            this.max_rank = 0;
            this.budget_id = '';
            this.budget_name = '';
            this.budget = [];
            this.budget['一级部门'] = '';
            this.budget['二级部门'] = '';
            this.budget['三级部门'] = '';
            this.budget['四级部门'] = '';
            this.budget['五级部门'] = '';
            this.budget['六级部门'] = '';
            this.budget['七级部门'] = '';
        }

        // 工具栏数据
        var tb_obj = JSON.parse('<?php echo $toolbar_json; ?>');

        var main_tb = new dhx.Toolbar('main_tb', {css:'toobar-class'});
        main_tb.data.add({id:'刷新', type:'button', value:'刷新'});
        main_tb.data.add({type:'separator'});
        if (tb_obj['新增授权'] == true)
        {
            main_tb.data.add({id:'新增下级部门', type:'button', value:'新增下级部门'});
        }
        if (tb_obj['修改授权'] == true)
        {
            main_tb.data.add({id:'修改部门信息', type:'button', value:'修改部门信息'});
        }
        if (tb_obj['新增授权'] == true || tb_obj['修改授权'] == true)
        {
            main_tb.data.add({id:'提交', type:'button', value:'提交'});
        }
        if (tb_obj['删除授权'] == true)
        {
            main_tb.data.add({type:'separator'});
            main_tb.data.add({id:'删除部门', type:'button', value:'删除部门'});
        }
        main_tb.data.add({type:'spacer'});
        if (tb_obj['SQL'] == true)
        {
            main_tb.data.add({id:'SQL', type:'button', value:'SQL'});
        }

        $$('infobox').style.height = document.documentElement.clientHeight * 0.035 + 'px';
        $$('infobox').innerHTML = '&nbsp&nbsp选定部门:';
        $$('footbox').innerHTML = '&nbsp&nbsp<b>' + <?php echo $func_id; ?>;

        var dept_str = '';
        var guid_selected = [];
        var submit_type = '';
        var editable = false;
        var button = '';

        var data_grid_api = null;
        var popup_grid_api = null;

        // tree视图
        var tree_obj = JSON.parse('<?php echo $dept_json; ?>');
        var tree = new dhx.Tree('tree_box');
        tree.data.parse(tree_obj);

        var tree_expand_obj = JSON.parse('<?php echo $tree_expand_json; ?>');
        for (var ii in tree_expand_obj)
        {
            tree.expand(tree_expand_obj[ii]);
        }

        // grid样式
        var grid_theme = agGrid.themeAlpine;

        //grid视图
        var value_obj = [];

        const grid_options = 
        {
            theme: grid_theme,
            columnDefs: 
            [
                {field:'表项', width:150, editable:false},
                {
                    field:'值', 
                    width:350, 
                    cellEditorSelector: (params) =>
                    {
                        let col_name = params.data.列名;

                        switch (params.data.表项)
                        {
                            case '属地':
                                return {
                                    component: 'agSelectCellEditor',
                                    params: {
                                        values: ['','北京总公司','河北分公司','四川分公司',,'河南分公司']
                                    },
                                };
                            case '有无下级部门':
                                return {
                                    component: 'agSelectCellEditor',
                                    params: {
                                        values: ['','有','无']
                                    },
                                };
                            case '预算表部门全称':
                                budget_arr.grid_api = params.api;
                                budget_arr.node_id = params.node.id;
                                budget_arr.col_name = col_name;

                                win_popup_set.show();

                                if (popup_grid_content == '')
                                {
                                    popup_grid_api = agGrid.createGrid($$('popup_set_grid'), budget_grid_options);
                                    popup_grid_content = 'BUDGET';
                                }

                                let budget = budget_value[params.data.部门];
                                let data_arr = [];
                                data_arr[0] = '';

                                for (let item in budget)
                                {
                                    if (budget_arr.budget[budget['上级部门级别']] != item) continue;
                                    for (let val in budget[item])
                                    {
                                        data_arr.push(budget[item][val]);
                                    }
                                }
                                return {
                                    component: 'agSelectCellEditor',
                                    params: {
                                        values: data_arr
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
                },
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

        data_grid_api = agGrid.createGrid($$('grid_box'), grid_options);

        // 弹窗选择窗口
        var win_popup_set = new dhx.Window(
        {
            title: '条件输入窗口',
            footer: true,
            modal: true,
            width: 600,
            height: 500,
            closable: true,
            movable: true
        });

        win_popup_set.footer.data.add(
        {
            type: 'button',
            id: '清空',
            value: '清空',
            view: 'flat',
            size: 'medium',
            color: 'primary',
        });

        win_popup_set.footer.data.add(
        {
            type: 'button',
            id: '确定',
            value: '确定',
            view: 'flat',
            size: 'medium',
            color: 'primary',
        });

        var html = '<div id="popup_set_grid" style="width:100%;height:100%;"></div>';
        win_popup_set.attachHTML(html);
        win_popup_set.hide();

        // 预算部门数据
        var menu_value = JSON.parse('<?php echo $menu_json; ?>');
        var popup_grid_content = '';
        var budget_value = JSON.parse('<?php echo $budget_json; ?>');
        var budget_grid_obj = JSON.parse('<?php echo $budget_rows_json; ?>');
        var budget_arr = new BudgetInfo();
        budget_arr.menu_1 = menu_value['menu_1'];
        budget_arr.menu_2 = menu_value['menu_2'];
        budget_arr.budget['一级部门'] = budget_grid_obj[0]['取值'];
        budget_arr.budget['二级部门'] = budget_grid_obj[1]['取值'];

        // 预算部门视图
        const budget_grid_options = 
        {
            theme: grid_theme,
            columnDefs:
            [
                {field:'部门', editable:false},
                {field:'级别', editable:false},
                {
                    field: '取值',
                    width: 250,
                    editable: (params) =>
                    {
                        if (params.data.部门 == '一级部门') 
                        {
                            return false;
                        }
                        return true;
                    },
                    cellEditorSelector: (params) => 
                    {
                        let budget = budget_value[params.data.部门];
                        let data_arr = [];
                        data_arr[0] = '';

                        for (let item in budget)
                        {
                            if (budget_arr.budget[budget['上级部门级别']] != item) continue;
                            for (let val in budget[item])
                            {
                                data_arr.push(budget[item][val]);
                            }
                        }

                        return {
                            component: 'agSelectCellEditor',
                            params: {
                                values: data_arr
                            },
                        };
                    },
                    onCellValueChanged: (params) => 
                    {
                        budget_arr.budget[params.data.部门] = params.newValue;
                        if (params.data.取值 != '')
                        {
                            budget_arr.max_rank = params.data.级别;
                        }

                        // 清空下级部门
                        budget_grid_api.forEachNode((rowNode, index) => 
                        {
                            if (rowNode.data['级别'] > budget_arr.max_rank)
                            {
                                rowNode.setDataValue('取值', '');
                            }
                        });
                    },
                }
            ],
            defaultColDef:
            {
                width: 120,
                editable: true,
                resizable: true
            },
            rowData: budget_grid_obj
        };

        win_popup_set.footer.events.on('click', function (id)
        {
            if (id == '清空')
            {
                budget_grid_obj = JSON.parse('<?php echo $budget_rows_json; ?>');
                popup_grid_api.setGridOption('rowData', budget_grid_obj);
            }
            else if (id == '确定')
            {
                // 获表中的数据
                let send_obj = {};
                send_obj['操作'] = id;
                send_obj['部门级别'] = budget_arr.max_rank;
                send_obj['部门全称'] = '';

                popup_grid_api.stopEditing();
                popup_grid_api.forEachNode((rowNode, index) => 
                {
                    send_obj[rowNode.data['部门']] = rowNode.data['取值'];
                    if (rowNode.data['取值'] != '')
                    {
                        if (send_obj['部门全称'] != '') send_obj['部门全称'] += '>>';
                        send_obj['部门全称'] += rowNode.data['取值'];
                    }
                });

                dhx.ajax.post('<?php base_url(); ?>/dept/budget_verify/<?php echo $func_id; ?>', send_obj).then(function (data)
                {
                    if (budget_arr.menu_1 != menu_value['menu_1'] || budget_arr.menu_2 != menu_value['menu_2'])
                    {
                        alert('页面已切换,请重新输入');
                        return;
                    }

                    win_popup_set.hide();

                    budget_arr.budget['一级部门'] = send_obj['一级部门'];
                    budget_arr.budget['二级部门'] = send_obj['二级部门'];
                    budget_arr.budget['三级部门'] = send_obj['三级部门'];
                    budget_arr.budget['四级部门'] = send_obj['四级部门'];
                    budget_arr.budget['五级部门'] = send_obj['五级部门'];
                    budget_arr.budget['六级部门'] = send_obj['六级部门'];
                    budget_arr.budget['七级部门'] = send_obj['七级部门'];

                    let api = budget_arr.grid_api;
                    api.stopEditing();
                    let row_node = api.getRowNode(budget_arr['node_id']);

                    row_node.setDataValue('值', data);
                }).catch(function (err)
                {
                    console.log(err);
                    alert('`校验预算部门信息`失败, ' + " " + err.statusText);
                });
            }
        });

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
                    var rowNode = data_grid_api.getRowNode(0);
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
                case 'SQL':
                    console.log('ID=[ ', '<?php echo $func_id; ?>', ' ]');
                    console.log('SQL=[ ', '<?php echo $SQL; ?>', ' ]');
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

            console.log('guid_selected=[', guid_selected, ']');

            dhx.ajax.post('<?php base_url(); ?>/dept/ajax/<?php echo $func_id; ?>', arg_obj).then(function (data)
            {
                value_obj = JSON.parse(data);
                grid_obj = JSON.parse(data);
                data_grid_api.setGridOption('rowData', grid_obj);
                editable = false;
                button = '查询部门信息';
                var item = id.split('^');

                $$('infobox').innerHTML = '&nbsp&nbsp选定部门 : ' + value_obj[4]['值'];
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
                case '有无下级部门':
                    return {
                        component: 'agSelectCellEditor',
                        params: {
                            values: ['','有','无']
                        },
                    };
                case '预算表部门全称':
                    budget_arr.grid_api = params.api;
                    budget_arr.node_id = params.node.id;
                    budget_arr.col_name = col_name;

                    win_popup_set.show();

                    if (popup_grid_content == '')
                    {
                        popup_grid_api = agGrid.createGrid($$('popup_set_grid'), budget_grid_options);
                        popup_grid_content = 'BUDGET';
                    }

                    let budget = budget_value[params.data.部门];
                    let data_arr = [];
                    data_arr[0] = '';

                    for (let item in budget)
                    {
                        if (budget_arr.budget[budget['上级部门级别']] != item) continue;
                        for (let val in budget[item])
                        {
                            data_arr.push(budget[item][val]);
                        }
                    }
                    return {
                        component: 'agSelectCellEditor',
                        params: {
                            values: data_arr
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

            data_grid_api.stopEditing();
            data_grid_api.forEachNode((rowNode, index) =>
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

            data_grid_api.forEachNode((rowNode, index) =>
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
                data_grid_api.setGridOption('rowData', grid_obj);
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

            data_grid_api.stopEditing();
            data_grid_api.forEachNode((rowNode, index) =>
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
            });

            if (ajax == -1)
            {
                return;
            }

            var arg_obj = {};
            arg_obj['操作'] = '新增下级部门';

            data_grid_api.forEachNode((rowNode, index) =>
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

            data_grid_api.stopEditing();
            data_grid_api.forEachNode((rowNode, index) =>
            {
                // 校验
                if (rowNode.data['表项'] == '属性' && rowNode.data['值'] != '查询部门信息')
                {
                    alert('`属性`不为`查询部门信息`,不能进行`删除部门`操作');
                    ajax = -1;
                }
                if (rowNode.data['表项'] == '有无下级部门' && rowNode.data['值'] == '有')
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
