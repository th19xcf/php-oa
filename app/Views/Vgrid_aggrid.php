<!-- v3.4.2.1.202202241335, from office -->
<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8'>
    <title>ag-grid_div</title>

    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/ag-grid/dist/styles/ag-grid.css'>
    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/ag-grid/dist/styles/ag-theme-alpine.css'>
    <script src='<?php base_url(); ?>/ag-grid/dist/ag-grid-locale-cn.js'></script>
    <script src='<?php base_url(); ?>/ag-grid/dist/ag-grid-community.noStyle.js'></script>

    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/dhtmlx/codebase/suite.css'>
    <script src='<?php base_url(); ?>/dhtmlx/codebase/suite.js'></script>

    <script src='<?php base_url(); ?>/assets/js/datepicker_brower.js'></script>
</head>

<body>
    <div id='databox' style='width:100%;'>
        <div id='data_tb'></div>
        <div id='data_grid' class='ag-theme-alpine' style='width:100%; height:92%; background-color:lightblue;'></div>
    </div>
    <div id='updatebox' style='width:100%;'>
        <div id='update_tb'></div>
        <div id='update_grid' class='ag-theme-alpine' style='width:100%; height:92%; background-color:lightblue;'></div>
    </div>
    <div id='conditionbox' style='width:100%;'>
        <div id='cond_tb'></div>
        <div id='cond_grid' class='ag-theme-alpine' style='width:100%; height:92%; background-color:lightblue;'></div>
    </div>
    <div id='footbox' style='width:100%; height:10px; margin-top:5px; background-color: lightblue;'></div>
    <a id='exp2xls'></a>

    <script type='text/javascript' charset='utf-8'>
        function $$(id)
        {
            return document.getElementById(id);
        }

        $$('databox').style.height = document.documentElement.clientHeight * 0.92 + 'px';
        $$('updatebox').style.height = document.documentElement.clientHeight * 0.92 + 'px';
        $$('conditionbox').style.height = document.documentElement.clientHeight * 0.92 + 'px';
        $$('footbox').style.height = document.documentElement.clientHeight * 0.033 + 'px';

        $$('databox').style.display = 'block';
        $$('updatebox').style.display = 'none';
        $$('conditionbox').style.display = 'none';
        $$('footbox').style.display = 'block';

        $$('footbox').innerHTML = '&nbsp&nbsp<b>条件:{} , 汇总:{} , 平均:{}</b>';

        function ColumnInfo()
        {
            this.col_name = '';
            this.fld_name = '';
            this.type = '';
            this.value = '';
            this.visible = true;
        }

        function CondInfo()
        {
            this.col_name = '';
            this.fld_name = '';
            this.type = '';
            this.group = '';
            this.average = '';
            this.cond_1 = '';
            this.arg_1 = '';
            this.and_or = '';
            this.cond_2 = '';
            this.arg_2 = '';
        }

        // 字段信息
        var tb_obj = JSON.parse('<?php echo $toolbar_json; ?>');

        var update_flag = '';  // modify或add

        // 生成主菜单栏
        var data_tb = new dhx.Toolbar('data_tb', {css:'toobar-class'});
        //data_tb.data.add({id:'名称', type:'title', value:'主菜单-->'});
        data_tb.data.add({id:'刷新', type:'button', value:'刷新'});
        data_tb.data.add({id:'分页', type:'button', value:'分页'});
        data_tb.data.add({id:'字段选择', type:'button', value:'字段选择'});
        data_tb.data.add({id:'设置条件', type:'button', value:'设置条件'});
        data_tb.data.add({id:'图形', type:'button', value:'图形'});
        data_tb.data.add({type:'separator'});
        if (tb_obj['修改授权'] == true)
        {
            data_tb.data.add({id:'修改', type:'button', value:'修改'});
        }
        if (tb_obj['新增授权'] == true)
        {
            data_tb.data.add({id:'新增', type:'button', value:'新增'});
        }
        data_tb.data.add({type:'spacer'});
        data_tb.data.add({id:'导出', type:'button', value:'导出'});

        // 生成修改新增用菜单栏
        var update_tb = new dhx.Toolbar('update_tb', {css:'toobar-class'});
        update_tb.data.add({id:'返回', type:'button', value:'返回'});
        update_tb.data.add({type:'separator'});
        update_tb.data.add({id:'清空', type:'button', value:'清空'});
        update_tb.data.add({id:'提交', type:'button', value:'提交'});

        // 生成设置条件用菜单栏
        var cond_tb = new dhx.Toolbar('cond_tb', {css:'toobar-class'});
        cond_tb.data.add({id:'返回', type:'button', value:'返回'});
        cond_tb.data.add({type:'separator'});
        cond_tb.data.add({id:'清空', type:'button', value:'清空'});
        cond_tb.data.add({id:'提交', type:'button', value:'提交'});

        // 生成data_grid
        var data_columns_obj = JSON.parse('<?php echo $data_col_json; ?>');

        var data_columns_arr = []; // 数据表使用
        data_columns_arr = Object.values(data_columns_obj);

        var data_grid_obj = JSON.parse('<?php echo $data_value_json; ?>');

        const data_grid_options = 
        {
            columnDefs: data_columns_arr,
            defaultColDef: 
            {
                width: 120,
                resizable: true
            },
            rowData: data_grid_obj,
            rowSelection: 'multiple',
            pagination: true,
            localeText: AG_GRID_LOCALE_CN
        };

        new agGrid.Grid($$('data_grid'), data_grid_options);

        data_grid_options.onGridReady = data_grid_ready;
        function data_grid_ready(event)
        {
            console.log('datagrid ready');
        }

        // 生成update_grid
        var columns_obj = JSON.parse('<?php echo $columns_json; ?>');
        var columns_arr = Object.values(columns_obj);
        var update_grid_obj = JSON.parse('<?php echo $update_value_json; ?>');
        var object_obj = JSON.parse('<?php echo $object_json; ?>');

        const update_grid_options = 
        {
            columnDefs: 
            [
                {field:'列名'},
                {field:'字段名', hide:true},
                {field:'列类型'},
                {field:'取值', editable:true, cellEditorSelector:cellEditorSelector}
            ],
            defaultColDef: 
            {
                width: 120,
                resizable: true
            },
            singleClickEdit: true,
            rowData: update_grid_obj,

            components:
            {
                datePicker: get_date_picker(),
            }
        };

        new agGrid.Grid($$('update_grid'), update_grid_options);

        // cond_grid
        var cond_grid_obj = JSON.parse('<?php echo $cond_value_json; ?>');
        const cond_grid_options = 
        {
            columnDefs:
            [
                {field:'列名', width:120, editable:false},
                {field:'字段名', width:120, editable:false},
                {field:'列类型', editable:false},
                {
                    field:'汇总',
                    cellEditor: 'agSelectCellEditor',
                    cellEditorParams: 
                    {
                        values: ['','√'],
                    },
                },
                {
                    field:'平均',
                    cellEditor: 'agSelectCellEditor',
                    cellEditorParams: 
                    {
                        values: ['','√'],
                    },
                },
                {
                    field:'条件1',
                    cellEditor: 'agSelectCellEditor',
                    cellEditorParams: 
                    {
                        values: ['','大于','等于','小于','大于等于','小于等于','不等于','包含','不包含'],
                    },
                },
                {field:'参数1', width:180, cellEditorSelector:cellEditorSelector},
                {
                    field:'条件关系', 
                    cellEditor: 'agSelectCellEditor',
                    cellEditorParams: 
                    {
                        values: ['', '并且', '或者'],
                    },
                },
                {
                    field:'条件2',
                    cellEditor: 'agSelectCellEditor',
                    cellEditorParams: 
                    {
                        values: ['','大于','等于','小于','大于等于','小于等于','不等于','包含','不包含'],
                    },
                },
                {field:'参数2', width:180, cellEditorSelector:cellEditorSelector}
            ],
            defaultColDef: 
            {
                width: 100,
                editable: true,
                resizable: true
            },
            singleClickEdit: true,
            rowData: cond_grid_obj,

            components:
            {
                datePicker: get_date_picker(),
            }
        };

        new agGrid.Grid($$('cond_grid'), cond_grid_options);

        // 图形设置
        // 提前生成录入窗口,否则得不到modify_grid
        var win_chart = new dhx.Window(
        {
            title: '图形参数设置窗口',
            footer: true,
            modal: true,
            width: 700,
            height: 500,
            closable: true,
            movable: true
        });

        win_chart.footer.data.add(
        {
            type: 'button',
            id: '新增',
            value: '新增',
            view: 'flat',
            size: 'medium',
            color: 'primary',
        });

        win_chart.footer.data.add(
        {
            type: 'button',
            id: '确定',
            value: '确定',
            view: 'flat',
            size: 'medium',
            color: 'primary',
        });

        var html = '<div id="chart_set_grid" class="ag-theme-alpine" style="width:100%;height:100%;"></div>';
        win_chart.attachHTML(html);
        win_chart.hide();

        const chart_grid_options = 
        {
            columnDefs:
            [
                {field:'坐标轴', width:120},
                {field:'对应字段', width:120},
                {field:'图形类型', width:120},
            ],
            defaultColDef: 
            {
                width: 120,
                editable: true,
                resizable: true
            },
            singleClickEdit: true,
            rowData: 
            [
                {'坐标轴':'X轴', '对应字段':'', '图形类型':''},
                {'坐标轴':'Y轴', '对应字段':'', '图形类型':''}
            ]
        };

        // 工具栏点击
        data_tb.events.on('click', function(id, e) 
        {
            switch (id)
            {
                case '刷新':
                    window.location.reload();
                    break;
                case '字段选择':
                    tb_select_field();
                    break;
                case '设置条件':
                    $$('databox').style.display = 'none';
                    $$('updatebox').style.display = 'none';
                    $$('conditionbox').style.display = 'block';
                    break;
                case '图形':
                    tb_chart();
                    break;
                case '修改':
                    var rows = data_grid_options.api.getSelectedRows();
                    if (rows.length == 0)
                    {
                        alert('请先选择要修改的记录');
                        break;
                    }
                    if (update_flag != 'modify')
                    {
                        // 清空
                        update_grid_obj = JSON.parse('<?php echo $update_value_json; ?>');
                        update_grid_options.api.setRowData(update_grid_obj);
                    }

                    update_flag = 'modify';
                    $$('databox').style.display = 'none';
                    $$('updatebox').style.display = 'block';
                    $$('conditionbox').style.display = 'none';
                    break;
                case '新增':
                    if (update_flag != 'add')
                    {
                        // 清空
                        update_grid_obj = JSON.parse('<?php echo $update_value_json; ?>');
                        update_grid_options.api.setRowData(update_grid_obj);
                    }

                    update_flag = 'add';
                    $$('databox').style.display = 'none';
                    $$('updatebox').style.display = 'block';
                    $$('conditionbox').style.display = 'none';
                    break;
                case '导出':
                    var href = '<?php base_url(); ?>/Frame/export/<?php echo $func_id; ?>';
                    $$('exp2xls').href = href;
                    $$('exp2xls').click();
                    break;
            }
        });

        // 工具栏点击
        update_tb.events.on('click', function(id, e) 
        {
            switch (id)
            {
                case '返回':
                    $$('databox').style.display = 'block';
                    $$('updatebox').style.display = 'none';
                    $$('conditionbox').style.display = 'none';
                    break;
                case '清空':
                    update_grid_obj = JSON.parse('<?php echo $update_value_json; ?>');
                    update_grid_options.api.setRowData(update_grid_obj);
                    break;
                case '提交':
                    update_submit(id);
                    break;
                case '条件窗返回':
                    $$('databox').style.display = 'block';
                    $$('updatebox').style.display = 'none';
                    break;
            }
        });

        // 条件栏点击
        cond_tb.events.on('click', function(id, e) 
        {
            switch (id)
            {
                case '返回':
                    $$('databox').style.display = 'block';
                    $$('updatebox').style.display = 'none';
                    $$('conditionbox').style.display = 'none';
                    break;
                case '清空':
                    cond_grid_obj = JSON.parse('<?php echo $cond_value_json; ?>');
                    cond_grid_options.api.setRowData(cond_grid_obj);
                    break;
                case '提交':
                    condition_submit(id);
                    break;
            }
        });

        // 选择字段是否显示
        function tb_select_field()
        {
            var checkbox_arr = [];
            var key = '<?php echo $primary_key; ?>';
            var columns_arr = data_grid_options.columnApi.getAllColumns();

            for (var ii in columns_arr)
            {
                if (columns_arr[ii]['colId'] == key) continue;

                var col = {};
                col['type'] = 'checkbox';
                col['text'] = columns_arr[ii]['colId'];
                col['id'] = columns_arr[ii]['colId'];
                col['checked'] = columns_arr[ii]['visible'];
                checkbox_arr.push(col);
            }

            var form = new dhx.Form('form_field_select', 
            {
                rows: checkbox_arr
            });

            form.events.on('change', function(value)
            {
                var checked = form.getItem(value).getValue();
                data_grid_options.columnApi.setColumnVisible(value, checked);
            });

            var win_field = new dhx.Window(
            {
                title: '选择显示字段',
                footer: true,
                modal: true,
                width: 350,
                height: 500,
                closable: true,
                movable: true
            });

            win_field.attach(form);
            win_field.show();
        }

        function tb_chart()
        {
            win_chart.show();
            new agGrid.Grid($$('chart_set_grid'), chart_grid_options);
        }

        function condition_submit(id)
        {
            var cond_arr = [];
            var group_flag = false;
            var average_flag = false;

            var cond_str = '';
            var group_str = '';
            var average_str = '';

            cond_grid_options.api.stopEditing();
            cond_grid_options.api.forEachNode((rowNode, index) => 
            {
                var ajax = false;
                var cond = new CondInfo();
                cond.col_name = rowNode.data['字段名'];
                cond.fld_name = rowNode.data['字段名'];
                cond.type = rowNode.data['列类型'];

                if (rowNode.data['汇总'] != '')
                {
                    cond.group = '1';
                    group_flag = true;
                }
                if (rowNode.data['平均'] != '')
                {
                    cond.average = '1';
                    average_flag = true;
                }

                cond.cond_1 = rowNode.data['条件1'];
                cond.arg_1 = rowNode.data['参数1'];
                cond.and_or = rowNode.data['条件关系'];
                cond.cond_2 = rowNode.data['条件2'];
                cond.arg_2 = rowNode.data['参数2'];

                if (cond.average!='' && rowNode.data['列类型']!='数值')
                {
                    alert("'" + cond.col_name + "'" + '类型不是数组,无法平均,错误');
                    return;
                }

                if (cond.cond_1!='' && cond.arg_1=='')
                {
                    alert("'" + cond.col_name + "'" + '参数1,错误');
                    return;
                }
                if (cond.cond_2!='' && cond.arg_2=='')
                {
                    alert("'" + cond.col_name + "'" + '参数2,错误');
                    return;
                }
                if (cond.cond_1!='' && cond.arg_2!='' && cond.and_or=='')
                {
                    alert("'" + cond.col_name + "'" + '条件关系,错误');
                    return;
                }

                if (cond.cond_1 !='')
                {
                    if (cond_str != '') cond_str = cond_str + ',';
                    cond_str = cond_str + cond.col_name + cond.cond_1 + cond.arg_1;
                    ajax = true;
                }
                if (cond.cond_2 != '')
                {
                    cond_str = cond_str + cond.and_or + cond.cond_2 + cond.arg_2;
                }

                if (cond.group != '')
                {
                    if (group_str != '') group_str = group_str + ',';
                    group_str = group_str + cond.col_name;
                    ajax = true;
                }
                if (cond.average != '')
                {
                    if (average_str != '') average_str = average_str + ',';
                    average_str = average_str + cond.col_name;
                }

                if (ajax == true) cond_arr.push(cond);
            });

            if (average_flag==true && group_flag==false)
            {
                alert('计算平均值, 必须设置汇总字段');
                return;
            }

            $$('footbox').innerHTML = '&nbsp&nbsp<b>条件:{' + cond_str + '} , 汇总:{' + group_str + '} , 平均:{' + average_str + '}</b>';

            dhx.ajax.post('<?php base_url(); ?>/Frame/set_condition/<?php echo $func_id; ?>', cond_arr).then(function (data)
            {
                data_grid_obj = JSON.parse(data);
                data_grid_options.api.setRowData(data_grid_obj);

                $$('databox').style.display = 'block';
                $$('updatebox').style.display = 'none';
                $$('conditionbox').style.display = 'none';
                $$('footbox').innerHTML = '&nbsp&nbsp<b>条件:{' + cond_str + '} , 汇总:{' + group_str + '} , 平均:{' + average_str + '}</b>';

                alert('设置条件成功');
            }).catch(function (err)
            {
                alert('设置条件错误, ' + " " + err.statusText);
            });
        }

        function update_submit(id)
        {
            var update_arr = [];
            var add_arr = [];

            update_grid_options.api.stopEditing();
            update_grid_options.api.forEachNode((rowNode, index) => 
            {
                var col = new ColumnInfo();
                col.col_name = rowNode.data['字段名'];
                col.fld_name = rowNode.data['字段名'];
                col.type = rowNode.data['列类型'];
                col.value = rowNode.data['取值'];

                add_arr.push(col);

                if (rowNode.data['取值'] != '')
                {
                    update_arr.push(col);
                }
            });

            if (update_flag == 'add')
            {
                dhx.ajax.post('<?php base_url(); ?>/Frame/add_row/<?php echo $func_id; ?>', add_arr).then(function (data)
                {
                    alert('新增记录成功');
                }).catch(function (err)
                {
                    alert('新增记录错误, ' + " " + err.statusText);
                });
            }

            else if (update_flag == 'modify')
            {
                // 选择的记录
                var rows = data_grid_options.api.getSelectedRows();

                var key = '<?php echo $primary_key; ?>';
                var key_values = '';

                for (var ii in rows)
                {
                    if (key_values == '')
                    {
                        key_values = data_grid_obj[rows[ii].序号-1][key];
                    }
                    else
                    {
                        key_values = key_values + ',' + data_grid_obj[rows[ii].序号-1][key];
                    }
                }

                var col = new ColumnInfo();
                col.col_name = key;
                col.fld_name = key;
                col.value = key_values;

                update_arr.push(col);

                dhx.ajax.post('<?php base_url(); ?>/Frame/update_row/<?php echo $func_id; ?>', update_arr).then(function (data)
                {
                    // 更改data_grid的记录(后期改变背景颜色)
                    var rows = data_grid_options.api.getSelectedRows();

                    for (var ii in rows)
                    {
                        for (var jj in update_arr)
                        {
                            for (var kk in update_arr[jj])
                            {
                                var id = kk;
                                var vv = update_arr[jj][kk];
                                if (vv == '<?php echo $primary_key; ?>') continue;
                                data_grid_obj[rows[ii].序号-1][id] = vv;
                            }
                        }
                    }

                    data_grid_options.api.refreshCells();

                    alert('数据更新成功');
                }).catch(function (err)
                {
                    alert('status' + " " + err.statusText);
                });
            }
        }

        function cellEditorSelector(params)
        {
            var col_name = params.data.列名;

            for (var ii in columns_obj)
            {
                if (columns_obj[ii].列名 != col_name) continue;
                switch (columns_obj[ii].赋值类型)
                {
                    case '下拉':
                        return {
                            component: 'agSelectCellEditor',
                            params: {
                                values: object_obj[params.data.列名]
                            },
                        };
                    case '日期':
                        return {
                            component: 'datePicker',
                            /*
                            params: {
                                values: object_obj[params.data.列名]
                            },
                            */
                        };
                }
                break;
            }
        }

    </script>

</body>

</html>