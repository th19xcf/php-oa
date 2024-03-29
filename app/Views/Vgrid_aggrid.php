<!-- v6.4.1.1.202403272240, from home -->
<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8'>
    <title>ag-grid_div</title>

    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/ag-grid/dist/styles/ag-grid.css'>
    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/ag-grid/dist/styles/ag-theme-alpine.css'>
    <script src='<?php base_url(); ?>/ag-grid/dist/ag-grid-locale-cn.js'></script>
    <script src='<?php base_url(); ?>/ag-grid/dist/ag-grid-community.noStyle.js'></script>
    <script src='<?php base_url(); ?>/assets/js/datepicker_brower.js'></script>

    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/dhtmlx/codebase/suite.css'>
    <script src='<?php base_url(); ?>/dhtmlx/codebase/suite.js'></script>

    <script src='<?php base_url(); ?>/echarts/echarts.js'></script>
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
    <div id='chartbox' style='width:100%;'>
        <div id='chart_tb'></div>
        <div id='chart_draw' style='width:100%; height:92%;'></div>
    </div>
    <div id='footbox' style='width:100%; height:10px; margin-top:5px; background-color: lightblue;'></div>
    <a id='exp2xls'></a>

    <script type='text/javascript' charset='utf-8'>
        function $$(id)
        {
            return document.getElementById(id);
        }

        function div_block(id)
        {
            $$('databox').style.display = 'none';
            $$('updatebox').style.display = 'none';
            $$('conditionbox').style.display = 'none';
            $$('chartbox').style.display = 'none';

            $$(id).style.display = 'block';
        }

        $$('databox').style.height = document.documentElement.clientHeight * 0.92 + 'px';
        $$('updatebox').style.height = document.documentElement.clientHeight * 0.92 + 'px';
        $$('conditionbox').style.height = document.documentElement.clientHeight * 0.92 + 'px';
        $$('chartbox').style.height = document.documentElement.clientHeight * 0.92 + 'px';
        $$('footbox').style.height = document.documentElement.clientHeight * 0.033 + 'px';

        div_block('databox');
        $$('footbox').style.display = 'block';

        foot_data = '&nbsp&nbsp<b>条件:{}, 汇总:{}, 合计:{}, 平均:{}, 最大:{}, 最小:{}, 计数:{}</b>';
        $$('footbox').innerHTML = foot_data;

        function ColumnInfo()
        {
            this.col_name = '';
            this.fld_name = '';
            this.type = '';
            this.value = '';
            this.modified = false;
            this.visible = true;
        }

        function CondInfo()
        {
            this.col_name = '';
            this.fld_name = '';
            this.type = '';
            this.group = '';
            this.cond_1 = '';
            this.arg_1 = '';
            this.and_or = '';
            this.cond_2 = '';
            this.arg_2 = '';
            this.sum_avg = '';
        }

        function Chart()
        {
            this.type = '';
            this.dataset = [];
            this.x1_name = '';
            this.x2_name = '';
            this.y1_name = '';
            this.y2_name = '';
            this.x1_data = [];
            this.y1_data = [];
            this.y2_data = [];
        }

        var chart = new Chart();

        // footbox显示
        var foot_data = '';
        var foot_upkeep = '';
        var foot_chart = '';

        var back_where = '<?php echo $back_where; ?>';
        var back_group = '<?php echo $back_group; ?>';
        var tip_column = '<?php echo $tip_column; ?>';

        foot_data = '&nbsp&nbsp<b>条件:{' + back_where + '}, 汇总:{' + back_group + '}, 合计:{}, 平均:{}, 最大:{}, 最小:{}</b>';
        $$('footbox').innerHTML = foot_data;

        // 字段数据
        var columns_obj = JSON.parse('<?php echo $columns_json; ?>');
        var columns_arr = Object.values(columns_obj);

        // 工具栏数据
        var tb_obj = JSON.parse('<?php echo $toolbar_json; ?>');

        var update_flag = '';  // modify或add

        // 条件选择
        var cond_object_value = JSON.parse('<?php echo $cond_obj_json; ?>');
        var update_object_value = JSON.parse('<?php echo $update_obj_json; ?>');

        // 生成主工具栏
        var data_tb = new dhx.Toolbar('data_tb', {css:'toobar-class'});
        data_tb.data.add({id:'刷新', type:'button', value:'刷新'});
        data_tb.data.add({id:'字段选择', type:'button', value:'字段选择'});
        data_tb.data.add({id:'设置条件', type:'button', value:'设置条件'});
        if (tb_obj['钻取授权'] == true)
        {
            data_tb.data.add({id:'数据钻取', type:'button', value:'数据钻取'});
        }
        data_tb.data.add({id:'图形', type:'button', value:'图形'});
        data_tb.data.add({type:'separator'});
        if (tb_obj['修改授权'] == true)
        {
            data_tb.data.add({id:'单条修改', type:'button', value:'单条修改'});
            data_tb.data.add({id:'多条修改', type:'button', value:'多条修改'});
        }
        if (tb_obj['新增授权'] == true)
        {
            data_tb.data.add({id:'新增', type:'button', value:'新增'});
        }
        if (tb_obj['删除授权'] == true)
        {
            data_tb.data.add({id:'删除', type:'button', value:'删除'});
        }
        data_tb.data.add({type:'separator'});
        data_tb.data.add({id:'title', type:'title', value:'分页'});
        data_tb.data.add(
        {
            id: '分页',
            type: 'selectButton',
            value: '500',
            items: [{id:'500',value:'500'},{id:'1000',value:'1000'},{id:'2000',value:'2000'}]
        });
        data_tb.data.add({type:'spacer'});
        if (tb_obj['导入授权'] == true)
        {
            data_tb.data.add({id:'导入', type:'button', value:'导入'});
        }
        if (tb_obj['导出授权'] == true)
        {
            data_tb.data.add({id:'导出', type:'button', value:'导出'});
        }

        // 生成修改新增用工具栏
        var update_tb = new dhx.Toolbar('update_tb', {css:'toobar-class'});
        update_tb.data.add({id:'返回', type:'button', value:'返回'});
        update_tb.data.add({type:'separator'});
        update_tb.data.add({id:'清空', type:'button', value:'清空'});
        update_tb.data.add({id:'提交', type:'button', value:'提交'});

        // 生成设置条件用工具栏
        var cond_tb = new dhx.Toolbar('cond_tb', {css:'toobar-class'});
        cond_tb.data.add({id:'返回', type:'button', value:'返回'});
        cond_tb.data.add({type:'separator'});
        cond_tb.data.add({id:'清空', type:'button', value:'清空'});
        cond_tb.data.add({id:'提交', type:'button', value:'提交'});

        // 生成图形用工具栏
        var chart_tb = new dhx.Toolbar('chart_tb', {css:'toobar-class'});
        chart_tb.data.add({id:'返回', type:'button', value:'返回'});
        chart_tb.data.add({type:'separator'});
        chart_tb.data.add({id:'刷新', type:'button', value:'刷新'});
        chart_tb.data.add({id:'设置', type:'button', value:'设置'});

        // 生成data_grid
        var data_page = 500;
        var data_columns_obj = JSON.parse('<?php echo $data_col_json; ?>');

        var data_columns_arr = []; // 数据表使用
        data_columns_arr = Object.values(data_columns_obj);

        var data_grid_obj = JSON.parse('<?php echo $data_value_json; ?>');
        var data_last_selected = [];

        // 字段排序,设置cell格式
        for (var ii in data_columns_arr)
        {
            for (var jj in columns_obj)
            {
                if (columns_obj[jj].列名 != data_columns_arr[ii].field) continue;

                if (columns_obj[jj].类型 == '数值')
                {
                    data_columns_arr[ii].comparator = value_sort;
                }
                if (columns_obj[jj].提示条件 != '' || columns_obj[jj].异常条件 != '')
                {
                    data_columns_arr[ii].cellStyle = set_cell_style;
                }
            }
        }

        const data_grid_options = 
        {
            columnDefs: data_columns_arr,
            defaultColDef: 
            {
                width: 120,
                resizable: true,
            },
            rowData: data_grid_obj,
            rowSelection: 'multiple',
            //onSelectionChanged: onSelectionChanged,
            pagination: true,
            localeText: AG_GRID_LOCALE_CN,
        };

        new agGrid.Grid($$('data_grid'), data_grid_options);

        data_grid_options.onGridReady = data_grid_ready;

        function data_grid_ready(event)
        {
            data_grid_options.api.paginationSetPageSize(Number(500));
            console.log('datagrid ready');
        }

        function onSelectionChanged()
        {
            //var row = data_grid_options.api.getSelectedRows();
            alert('selectionchanged');
        }

        function onCellValueChanged(params)
        {
            cond_object_value[params.data.列名] = params.newValue;
        }

        // 生成update_grid
        var update_grid_obj = JSON.parse('<?php echo $update_value_json; ?>');
        var object_obj = JSON.parse('<?php echo $object_json; ?>');

        var column_name_arr = [];
        for (var ii in columns_arr)
        {
            column_name_arr.push(columns_arr[ii]['列名']);
        }

        const update_grid_options = 
        {
            columnDefs: 
            [
                {
                    field:'修改项',
                    width:150,
                    editable:false,
                    headerCheckboxSelection:true, 
                    checkboxSelection:true
                },
                {field:'列名'},
                {field:'字段名', hide:true},
                {field:'列类型'},
                {field:'是否可修改'},
                {field:'是否必填'},
                {field:'取值', width:200, cellEditorSelector:cellEditorSelector}
            ],
            defaultColDef: 
            {
                width: 120,
                resizable: true,
                editable: (params) =>
                {
                    // 根据配置判断是否可以修改
                    if (params.colDef.field != '取值') return false;

                    var col_name = params.data.列名;

                    for (var ii in columns_obj)
                    {
                        if (columns_obj[ii].列名 != col_name) continue;
                        return (columns_obj[ii].可修改 == '1' || columns_obj[ii].可修改 == '2') ? true : false;
                    }

                    return false;
                }
            },
            rowSelection: 'multiple',
            rowData: update_grid_obj,

            components:
            {
                datePicker: get_date_picker(),
            }
        };

        new agGrid.Grid($$('update_grid'), update_grid_options);

        // cond_grid
        var cond_grid_obj = JSON.parse('<?php echo $cond_value_json; ?>');
        var cond_model = '<?php echo $cond_model; ?>';
        var cond_col_arr = [];

        if (cond_model == '数据查询')
        {
            cond_col_arr =
            [
                {field:'列名', width:120, editable:false},
                {field:'字段名', width:120, editable:false},
                {field:'列类型', editable:false},
                {field:'汇总', cellEditorSelector:cellEditorSelector},
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
                {field:'参数2', width:180, cellEditorSelector:cellEditorSelector},
                {
                    field:'计算方式',
                    cellEditor: 'agSelectCellEditor',
                    cellEditorParams: 
                    {
                        values: ['', '合计', '平均', '最大', '最小', '计数'],
                    },
                },
            ];
        }
        else
        {
            cond_col_arr =
            [
                {field:'列名', width:120, editable:false},
                {field:'字段名', width:120, editable:false},
                {field:'列类型', width:120, editable:false},
                {field:'是否必填', width:120, editable:false},
                {field:'取值', width:200, cellEditorSelector:cellEditorSelector}
            ];
        }

        const cond_grid_options = 
        {
            columnDefs: cond_col_arr,
            defaultColDef: 
            {
                width: 100,
                editable: true,
                resizable: true
            },
            singleClickEdit: true,
            rowData: cond_grid_obj,

            //onCellValueChanged : (params) => {alert('cellchanged');},
            onCellValueChanged: onCellValueChanged,

            components:
            {
                datePicker: get_date_picker(),
            }
        };

        new agGrid.Grid($$('cond_grid'), cond_grid_options);

        // 部门选择窗口
        var dept_grid_obj = [];
        var win_dept_set = new dhx.Window(
        {
            title: '部门设置',
            footer: true,
            modal: true,
            width: 700,
            height: 500,
            closable: true,
            movable: true
        });

        win_dept_set.footer.data.add(
        {
            type: 'button',
            id: '清空',
            value: '清空',
            view: 'flat',
            size: 'medium',
            color: 'primary',
        });

        win_dept_set.footer.data.add(
        {
            type: 'button',
            id: '确定',
            value: '确定',
            view: 'flat',
            size: 'medium',
            color: 'primary',
        });

        var html = '<div id="dept_set_grid" class="ag-theme-alpine" style="width:100%;height:100%;"></div>';
        win_dept_set.attachHTML(html);
        win_dept_set.hide();

        var dept_grid_new = false;
        const dept_grid_options = 
        {
            columnDefs:
            [
                {field:'列名', editable:false},
                {field:'字段名', editable:false},
                {field:'取值', width:200, cellEditorSelector:dept_select}
            ],
            defaultColDef:
            {
                width: 120,
                editable: true,
                resizable: true
            },
            rowData:
            [
                {'列名':'一级部门', '字段名':'一级部门', '取值':''},
                {'列名':'二级部门', '字段名':'二级部门', '取值':''},
                {'列名':'三级部门', '字段名':'三级部门', '取值':''},
                {'列名':'四级部门', '字段名':'四级部门', '取值':''},
            ]
        };


        // 图形设置
        var chart_grid_obj = [];
        var win_chart_set = new dhx.Window(
        {
            title: '图形参数设置窗口',
            footer: true,
            modal: true,
            width: 700,
            height: 500,
            closable: true,
            movable: true
        });

        win_chart_set.footer.data.add(
        {
            type: 'button',
            id: '新增',
            value: '新增',
            view: 'flat',
            size: 'medium',
            color: 'primary',
        });

        win_chart_set.footer.data.add(
        {
            type: 'button',
            id: '删除',
            value: '删除',
            view: 'flat',
            size: 'medium',
            color: 'primary',
        });

        win_chart_set.footer.data.add(
        {
            type: 'button',
            id: '确定',
            value: '确定',
            view: 'flat',
            size: 'medium',
            color: 'primary',
        });

        var html = '<div id="chart_set_grid" class="ag-theme-alpine" style="width:100%;height:100%;"></div>';
        win_chart_set.attachHTML(html);
        win_chart_set.hide();

        var chart_grid_new = false;
        const chart_grid_options = 
        {
            columnDefs:
            [
                {
                    field: '行选择',
                    width: 100,
                    checkboxSelection: true,
                },
                {
                    field: '字段名称',
                    width: 150,
                    cellEditor: 'agSelectCellEditor',
                    cellEditorParams: 
                    {
                        values: column_name_arr,
                    },
                },
                {
                    field: '坐标轴',
                    width: 120,
                    cellEditor: 'agSelectCellEditor',
                    cellEditorParams: 
                    {
                        values: ['X轴 (下方)','X轴 (上方)','Y轴 (左侧)','Y轴 (右侧)'],
                    },
                },
                {
                    field: '图形类型',
                    width: 120,
                    cellEditor: 'agCellEditor',
                    cellEditorParams:
                    {
                        values: ['饼图','折线图','柱图', '散点图', '雷达图'],
                    },
                },
            ],
            defaultColDef:
            {
                width: 120,
                editable: true,
                resizable: true
            },
            singleClickEdit: true,
            /*
            rowData:
            [
                {'行选择':'', '选择字段':'', '坐标轴':'', '图形类型':''},
                {'行选择':'', '选择字段':'', '坐标轴':'', '图形类型':''}
            ]
            */
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
                    div_block('conditionbox');
                    break;
                case '图形':
                    div_block('chartbox');
                    tb_chart();
                    break;
                case '单条修改':
                case '多条修改':
                    var rows = data_grid_options.api.getSelectedRows();
                    if (rows.length == 0)
                    {
                        alert('请先选择要修改的记录');
                        break;
                    }
                    if (id == '单条修改' && rows.length > 1)
                    {
                        alert('`单条修改`, 只能选择1条记录');
                        break;
                    }

                    update_flag = id;

                    if ((id == '单条修改' && rows[0] != data_last_selected) || id == '多条修改')
                    {
                        data_last_selected = rows[0];

                        // 清空
                        rowData = [];
                        for (let ii in columns_obj)
                        {
                            let obj = {};
                            obj['列名'] = columns_obj[ii].列名;
                            obj['字段名'] = columns_obj[ii].字段名;
                            obj['列类型'] = columns_obj[ii].类型;
                            obj['是否可修改'] = columns_obj[ii].可修改 == '0' ? '否' : '是';
                            obj['是否必填'] = columns_obj[ii].不可为空 == '0' ? '否' : '是';
                            obj['取值'] = '';

                            if (id == '单条修改')
                            {
                                for (var idx in data_last_selected)
                                {
                                    if (columns_obj[ii].列名 != idx) continue;
                                    obj['取值'] = data_last_selected[idx];
                                    break;
                                }
                            }

                            rowData.push(obj);
                        }

                        update_grid_options.api.setRowData(rowData);
                    }

                    $$('footbox').innerHTML = '&nbsp&nbsp<b>提交记录:{' + foot_upkeep + '}</b>';
                    div_block('updatebox');
                    break;
                case '新增':
                    if (update_flag != id)
                    {
                        update_flag = id;
                        // 清空
                        update_grid_obj = JSON.parse('<?php echo $add_value_json; ?>');
                        update_grid_options.api.setRowData(update_grid_obj);
                    }

                    $$('footbox').innerHTML = '&nbsp&nbsp<b>提交记录:{' + foot_upkeep + '}</b>';
                    div_block('updatebox');
                    break;
                case '删除':
                    var rows = data_grid_options.api.getSelectedRows();
                    if (rows.length == 0)
                    {
                        alert('请先选择要删除的记录');
                    }
                    else
                    {
                        delete_submit();
                    }
                    break;
                case '数据钻取':
                    var rows = data_grid_options.api.getSelectedRows();
                    if (rows.length == 0)
                    {
                        alert('请先选择要钻取的记录');
                        break;
                    }
                    if (rows.length > 1)
                    {
                        alert('只能选择1条记录');
                        break;
                    }

                    var nl_str = '<?php echo $next_func_condition; ?>';
                    var nl_arr = nl_str.split(',');
                    var send_obj = {};
                    var ajax = false;

                    for (var ii in nl_arr)
                    {
                        if (rows[0][nl_arr[ii]] == '') continue;
                        send_obj[nl_arr[ii]] = rows[0][nl_arr[ii]];
                        ajax = true;
                    }

                    if (ajax == false)
                    {
                        alert('钻取条件都为空,无法钻取');
                        break;
                    }

                    send_str = JSON.stringify(send_obj);

                    parent.window.goto('<?php echo $next_func_id; ?>','钻取-'+'<?php echo $next_func_name; ?>','frame/init/<?php echo $next_func_id; ?>/<?php echo $func_id; ?>/'+send_str);
                    break;
                case '导入':
                    parent.window.goto('<?php echo $import_func_id; ?>','导入-'+'<?php echo $import_func_name; ?>','upload/init/<?php echo $import_func_id; ?>');
                    break;
                case '导出':
                    var href = '<?php base_url(); ?>/frame/export/<?php echo $func_id; ?>';
                    $$('exp2xls').href = href;
                    $$('exp2xls').click();
                    break;
            }
        });

        data_tb.events.on('change', function(id,status,updatedItem)
        {
            if (id == '分页' && data_page != updatedItem['value'])
            {
                data_page =  updatedItem['value'];
                data_grid_options.api.paginationSetPageSize(Number(data_page));
            }
        });

        // 修改窗口,工具栏点击
        update_tb.events.on('click', function(id, e) 
        {
            switch (id)
            {
                case '返回':
                    div_block('databox');
                    $$('footbox').innerHTML = foot_data;
                    break;
                case '清空':
                    update_grid_obj = JSON.parse('<?php echo $update_value_json; ?>');
                    update_grid_options.api.setRowData(update_grid_obj);
                    break;
                case '提交':
                    update_submit(id);
                    break;
            }
        });

        // 条件窗口,工具栏点击
        cond_tb.events.on('click', function(id, e) 
        {
            switch (id)
            {
                case '返回':
                    div_block('databox');
                    $$('footbox').innerHTML = foot_data;
                    break;
                case '清空':
                    cond_grid_obj = JSON.parse('<?php echo $cond_value_json; ?>');
                    cond_grid_options.api.setRowData(cond_grid_obj);
                    break;
                case '提交':
                    if (cond_model == '数据查询')
                    {
                        cond_query_submit(id);
                    }
                    else
                    {
                        cond_sp_submit(id);
                    }
                    break;
            }
        });

        // 图形窗口,工具栏点击
        chart_tb.events.on('click', function(id, e) 
        {
            switch (id)
            {
                case '返回':
                    div_block('databox');
                    $$('footbox').innerHTML = foot_data;
                    break;
                case '刷新':
                    chart_draw();
                    break;
                case '设置':
                    tb_chart();
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
            win_chart_set.show();
            if (chart_grid_new == false)
            {
                new agGrid.Grid($$('chart_set_grid'), chart_grid_options);
                chart_grid_new = true;
            }
        }

        function cond_query_submit(id)
        {
            var cond_arr = [];
            var group_flag = false;
            var sum_flag = false;
            var average_flag = false;

            var cond_str = '';
            var group_str = '';
            var sum_str = '';
            var average_str = '';
            var max_str = '';
            var min_str = '';
            var count_str = '';

            cond_grid_options.api.stopEditing();
            cond_grid_options.api.forEachNode((rowNode, index) => 
            {
                if (rowNode.data['计算方式']=='合计' && rowNode.data['列类型']!='数值')
                {
                    alert("'" + rowNode.data['字段名'] + "'" + '类型不是数值,无法合计,请重新设置');
                    return;
                }
                if (rowNode.data['计算方式']=='平均' && rowNode.data['列类型']!='数值')
                {
                    alert("'" + rowNode.data['字段名'] + "'" + '类型不是数值,无法平均,请重新设置');
                    return;
                }
                if (rowNode.data['条件1']!='' && rowNode.data['参数1']=='')
                {
                    alert("'" + rowNode.data['字段名'] + "'" + '参数1,错误');
                    return;
                }
                if (rowNode.data['条件2']!='' && rowNode.data['参数2']=='')
                {
                    alert("'" + rowNode.data['字段名'] + "'" + '参数2,错误');
                    return;
                }
                if (rowNode.data['条件1']!='' && rowNode.data['条件2']!='' && rowNode.data['条件关系']=='')
                {
                    alert("'" + rowNode.data['字段名'] + "'" + '条件关系,错误');
                    return;
                }

                var ajax = false;
                var cond = new CondInfo();
                cond.col_name = rowNode.data['列名'];
                cond.fld_name = rowNode.data['字段名'];
                cond.type = rowNode.data['列类型'];
                cond.cond_1 = rowNode.data['条件1'];
                cond.arg_1 = rowNode.data['参数1'];
                cond.and_or = rowNode.data['条件关系'];
                cond.cond_2 = rowNode.data['条件2'];
                cond.arg_2 = rowNode.data['参数2'];
                cond.sum_avg = rowNode.data['计算方式'];

                if (rowNode.data['汇总'] == '√')
                {
                    cond.group = '1';
                    group_flag = true;
                    ajax = true;
                }
                if (rowNode.data['计算方式'] != '')
                {
                    cond.sum_avg = rowNode.data['计算方式'];
                    ajax = true;
                }
                if (cond.cond_1 != '')
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

                switch (cond.sum_avg)
                {
                    case '合计':
                        if (sum_str != '') sum_str = sum_str + ',';
                        sum_str = sum_str + cond.col_name;
                        break;
                    case '平均':
                        if (average_str != '') average_str = average_str + ',';
                        average_str = average_str + cond.col_name;
                        break;
                    case '最大':
                        if (max_str != '') max_str = max_str + ',';
                        max_str = max_str + cond.col_name;
                        break;
                    case '最小':
                        if (min_str != '') min_str = min_str + ',';
                        min_str = min_str + cond.col_name;
                        break;
                    case '计数':
                        if (count_str != '') count_str = count_str + ',';
                        count_str = count_str + cond.col_name;
                        break;
                }

                if (ajax == true) cond_arr.push(cond);
            });

            if (sum_flag==true && group_flag==false)
            {
                alert('计算合计值, 必须设置汇总字段');
                return;
            }

            if (average_flag==true && group_flag==false)
            {
                alert('计算平均值, 必须设置汇总字段');
                return;
            }

            dhx.ajax.post('<?php base_url(); ?>/frame/set_query_condition/<?php echo $func_id; ?>', cond_arr).then(function (data)
            {
                data_grid_obj = JSON.parse(data);
                data_grid_options.api.setRowData(data_grid_obj);

                div_block('databox');

                var disp_where = '';
                if (back_where != '')
                {
                    disp_where = back_where;
                    if (cond_str != '') disp_where = back_where + '&' + cond_str;
                }
                else
                {
                    if (cond_str != '') disp_where = cond_str;
                }

                var disp_group = '';
                if (back_group != '')
                {
                    disp_group = back_group;
                    if (group_str != '') disp_group = back_group + '&' + group_str;
                }
                else
                {
                    if (group_str != '') disp_group = group_str;
                }

                foot_data = '&nbsp&nbsp<b>条件:{' + disp_where + '} , 汇总:{' + disp_group + '} , 合计:{' + sum_str + '}, 平均:{' + average_str + '}, 最大:{' + max_str + '}, 最小:{' + min_str + '}, 计数:{' + count_str + '}</b>';
                $$('footbox').innerHTML = foot_data;
            }).catch(function (err)
            {
                alert('设置条件错误, ' + " " + err.statusText);
            });
        }

        function cond_sp_submit(id)
        {
            var ajax = true;
            var cond_arr = [];

            cond_grid_options.api.stopEditing();
            cond_grid_options.api.forEachNode((rowNode, index) => 
            {
                if (rowNode.data['取值'] == undefined)
                {
                    rowNode.data['取值'] = '';
                }

                if (rowNode.data['是否必填'] == '是' && rowNode.data['取值'] == '')
                {
                    alert("'" + rowNode.data['字段名'] + "'" + '是必填项, 请填写');
                    ajax = false;
                    return;
                }

                var col = new ColumnInfo();
                col.col_name = rowNode.data['列名'];
                col.fld_name = rowNode.data['字段名'];
                col.type = rowNode.data['列类型'];
                col.value = rowNode.data['取值'];
                col.modified = true;

                cond_arr.push(col);
            });

            if (ajax == false) return;

            dhx.ajax.post('<?php base_url(); ?>/frame/set_sp_condition/<?php echo $func_id; ?>', cond_arr).then(function (data)
            {
                data_grid_obj = JSON.parse(data);
                data_grid_options.api.setRowData(data_grid_obj);

                div_block('databox');
            }).catch(function (err)
            {
                alert('设置条件错误, ' + " " + err.statusText);
            });
        }

        function update_submit(id)
        {
            var ajax = 0;
            var send_arr = [];

            update_grid_options.api.stopEditing();

            // 获得要提交的数据
            if (update_flag == '多条修改')  // 多条
            {
                update_grid_options.api.stopEditing();
                update_grid_options.api.forEachNode((rowNode, index) => 
                {
                    if (rowNode.data['取值'] != '')
                    {
                        var col = new ColumnInfo();
                        col.col_name = rowNode.data['列名'];
                        col.fld_name = rowNode.data['字段名'];
                        col.type = rowNode.data['列类型'];
                        col.value = rowNode.data['取值'];
                        col.modified = true;

                        send_arr.push(col);
                        ajax = 1;
                    }
                });

                if (send_arr.length == 0)
                {
                    alert('所有内容没有修改,不提交');
                    return;
                }

                /*
                var rows = update_grid_options.api.getSelectedRows();
                if (rows.length == 0)
                {
                    alert('请选择要提交修改的条目');
                    return;
                }
                for (var ii in rows)
                {
                    if (rows[ii]['取值'] == '')
                    {
                        if (confirm('\"' + rows[ii]['列名'] + '\" 的值为空,请确认是否有效?') == false)
                        {
                            return;
                        }
                    }

                    var col = new ColumnInfo();
                    col.col_name = rows[ii]['列名'];
                    col.fld_name = rows[ii]['字段名'];
                    col.type = rows[ii]['列类型'];
                    col.value = rows[ii]['取值'];
                    col.modified = true;

                    send_arr.push(col);
                    ajax = 1;
                }
                */
            }

            else  // 单条&新增
            {
                update_grid_options.api.forEachNode((rowNode, index) => 
                {
                    var col = new ColumnInfo();
                    col.col_name = rowNode.data['列名'];
                    col.fld_name = rowNode.data['字段名'];
                    col.type = rowNode.data['列类型'];
                    col.value = rowNode.data['取值'];
                    if (rowNode.data['取值'] != data_last_selected[col.col_name])
                    {
                        col.modified = true;
                        ajax = 1;
                    }

                    send_arr.push(col);
                });
            }

            // 数据不可为空校验
            for (var ii in send_arr)
            {
                for (var jj in columns_obj)
                {
                    if (columns_obj[jj].列名 != send_arr[ii]['col_name']) continue;

                    if(columns_obj[jj].不可为空 == '1' && send_arr[ii]['value'] == '')
                    {
                        alert('`' + send_arr[ii]['col_name'] + '`的值不能为空');
                        return;
                    }
                }
            }

            if (update_flag == '新增')
            {
                dhx.ajax.post('<?php base_url(); ?>/frame/add_row/<?php echo $func_id; ?>', send_arr).then(function (data)
                {
                    alert(data);
                }).catch(function (err)
                {
                    alert('新增记录错误, ' + ' ' + err.statusText);
                });
            }

            else if (update_flag == '单条修改' || update_flag == '多条修改')
            {
                if (ajax == 0)
                {
                    alert('记录没有改动,提交失败');
                    return;
                }

                foot_upkeep = '';
                for (var ii in send_arr)
                {
                    if (foot_upkeep != '') foot_upkeep = foot_upkeep + ',';
                    foot_upkeep = foot_upkeep + send_arr[ii]['col_name'];
                }
                $$('footbox').innerHTML = '&nbsp&nbsp<b>提交记录:{' + foot_upkeep + '}</b>';

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
                col.type = '主键';
                col.value = key_values;

                send_arr.push(col);

                var data_model = '<?php echo $data_model; ?>';
                var active_date = ''; //生效日期

                //数据检查
                for (var ii in send_arr)
                {
                    if (send_arr[ii]['fld_name'] == '记录开始日期')
                    {
                        if (send_arr[ii]['value'] == '')
                        {
                            alert('生效日期不能为空,请重新填写');
                            return;
                        }
                        active_date = send_arr[ii]['value'];
                        break;
                    }
                }

                if (data_model == '2' && active_date == '')
                {
                    alert('数据模式=2,生效日期不能为空,请重新填写');
                    return;
                }

                var url = '<?php base_url(); ?>/frame/update_row/<?php echo $func_id; ?>';
                dhx.ajax.post(url, send_arr).then(function (data)
                {
                    alert(data);
                    window.location.reload();
                }).catch(function (err)
                {
                    alert('status' + " " + err.statusText);
                });
            }
        }

        function delete_submit(id)
        {
            var send_arr = [];

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
            col.type = '主键';
            col.value = key_values;

            send_arr.push(col);

            var url = '<?php base_url(); ?>/frame/delete_row/<?php echo $func_id; ?>';
            dhx.ajax.post(url, send_arr).then(function (data)
            {
                alert(data);
                window.location.reload();
            }).catch(function (err)
            {
                alert('status' + " " + err.statusText);
            });
        }

        function value_sort(valueA, valueB, nodeA, nodeB, isInverted)
        {
            return valueA - valueB;
        }

        // 条件判断
        function get_condition(params, type, str)
        {
            var f_name = '';
            var f_type = type;
            var f_val;
            var opt = '';
            var v_name = '';
            var v_type = '';
            var v_val;
            var value = '';
            error = false;

            opt_pos = -1;
            if (str.search('>=') != -1)
            {
                opt_pos = str.search('>=');
                f_name = str.substr(0, opt_pos);
                opt = str.substr(opt_pos, 2);
                v_name = str.substr(opt_pos + 2);
            }
            else if (str.search('<=') != -1)
            {
                opt_pos = str.search('<=');
                f_name = str.substr(0, opt_pos);
                opt = str.substr(opt_pos, 2);
                v_name = str.substr(opt_pos + 2);
            }
            else if (str.search('!=') != -1)
            {
                opt_pos = str.search('!=');
                f_name = str.substr(0, opt_pos);
                opt = str.substr(opt_pos, 2);
                v_name = str.substr(opt_pos + 2);
            }
            else if (str.search('>') != -1)
            {
                opt_pos = str.search('>');
                f_name = str.substr(0, opt_pos);
                opt = str.substr(opt_pos, 1);
                v_name = str.substr(opt_pos + 1);
            }
            else if (str.search('<') != -1)
            {
                opt_pos = str.search('<');
                f_name = str.substr(0, opt_pos);
                opt = str.substr(opt_pos, 1);
                v_name = str.substr(opt_pos + 1);
            }
            else if (str.search('=') != -1)
            {
                opt_pos = str.search('=');
                f_name = str.substr(0, opt_pos);
                opt = str.substr(opt_pos, 1);
                v_name = str.substr(opt_pos + 1);
            }

            if (f_name == '')
            {
                f_name = params.colDef.field;
                f_val = params.data[f_name];
            }
            else
            {
                if (f_name.indexOf('$') != -1)
                {
                    f_name = f_name.replace('$','');
                    f_val = params.data[f_name];
                }
            }

            for (var ii in columns_obj)
            {
                if (f_name != columns_obj[ii].列名) continue;
                f_type = columns_obj[ii].类型;
                break;
            }

            if (v_name.indexOf('$') != -1)
            {
                v_name = v_name.replace('$','');
                v_val = params.data[v_name];

                for (var ii in columns_obj)
                {
                    if (v_name != columns_obj[ii].列名) continue;
                    v_type = columns_obj[ii].类型;
                    break;
                }
            }
            else
            {
                v_val = v_name;
            }

            if (f_type == '数值')
            {
                val_1 = Number(f_val);
                val_2 = Number(v_val);

                switch (opt)
                {
                    case '>':
                        if (val_1 > val_2) error = true;
                        break;
                    case '<':
                        if (val_1 < val_2) error = true;
                        break;
                    case '=':
                        if (val_1 = val_2) error = true;
                        break;
                    case '>=':
                        if (val_1 >= val_2) error = true;
                        break;
                    case '<=':
                        if (val_1 <= val_2) error = true;
                        break;
                    case '!=':
                        if (val_1 != val_2) error = true;
                        break;
                }
            }
            else
            {
                switch (opt)
                {
                    case '=':
                        if (f_val.indexOf(v_val) != -1) error = true;
                        break;
                    case '!=':
                        if (f_val.indexOf(v_val) == -1) error = true;
                        break;
                }
            }

            return error;
        }

        // 单元格提示或异常条件
        function get_cell_condition_1(params, type, str)
        {
            var str1 = '', str2 = '';
            var rc = true, rc1 = true, rc2 = true;

            str = str.replace('；',';');

            if (str.indexOf(';') != -1)
            {
                str1 = str.substr(0, str.indexOf(';'));
                str2 = str.substr(str.indexOf(';')+1);

                str1 = str1.trim();
                str2 = str2.trim();

                rc1 = get_cell_condition_2(params, type, str1);
                rc2 = get_cell_condition_2(params, type, str2);

                rc = rc1 || rc2;
            }
            else
            {
                str = str.trim();
                rc = get_cell_condition_2(params, type, str);
            }

            return rc;
        }

        function get_cell_condition_2(params, type, str)
        {
            var str1 = '', str2 = '';
            var rc = true, rc1 = true, rc2 = true;

            if (str.indexOf('and') != -1)
            {
                str1 = str.substr(0, str.indexOf('and'));
                str2 = str.substr(str.indexOf('and')+3);

                str1 = str1.trim();
                str2 = str2.trim();

                rc1 = get_condition(params, type, str1);
                rc2 = get_condition(params, type, str2);

                rc = rc1 && rc2;
            }
            else if (str.indexOf('or') != -1)
            {
                str1 = str.substr(0, str.indexOf('or'));
                str2 = str.substr(str.indexOf('or')+2);

                str1 = str1.trim();
                str2 = str2.trim();

                rc1 = get_condition(params, type, str1);
                rc2 = get_condition(params, type, str2);

                rc = rc1 || rc2;
            }
            else
            {
                str = str.trim();
                rc = get_condition(params, type, str);
            }

            return rc;
        }

        // 单元格格式
        function get_cell_style(params, type, str, style_str, style_default)
        {
            var style_obj = {};

            if (style_str != '')
            {
                var style_arr = style_str.split(',');

                for (var ii in style_arr)
                {
                    var item_arr = style_arr[ii].split(':');
                    style_obj[item_arr[0]] = item_arr[1];
                }
            }
            else
            {
                style_obj = style_default;
            }

            var rc = get_cell_condition_1(params, type, str);
            if (rc)
            {
                return style_obj;
            }

            return '';
        }

        function set_cell_style(params)
        {
            for (var jj in columns_obj)
            {
                if (params.colDef.field != columns_obj[jj].列名) continue;

                if (columns_obj[jj].提示样式 == '' && columns_obj[jj].异常样式 == '')
                {
                    return null;
                }

                if (columns_obj[jj].异常条件 != '')
                {
                    str = columns_obj[jj].异常条件;
                    style_str = columns_obj[jj].异常样式;
                    style_default = {'color':'red','font-weight':'bold','background-color':'#f7acbc'};

                    rc = get_cell_style(params, columns_obj[jj].类型, str, style_str, style_default);
                    if (rc != '')
                    {
                        return rc;
                    }
                }

                if (columns_obj[jj].提示条件 != '')
                {
                    str = columns_obj[jj].提示条件;
                    style_str = columns_obj[jj].提示样式;
                    style_default = {'color':'green','font-weight':'bold'};

                    rc = get_cell_style(params, columns_obj[jj].类型, str, style_str, style_default);
                    if (rc != '')
                    {
                        return rc;
                    }
                }
            }

            return null;
        }

        function cellEditorSelector(params)
        {
            let col_name = params.data.列名;

            for (var ii in columns_obj)
            {
                if (columns_obj[ii].列名 != col_name) continue;

                if (params.colDef.field == '汇总' && columns_obj[ii].可汇总 == '1')
                {
                    return {
                        component: 'agSelectCellEditor',
                        params: {
                            values: ['','√'],
                            },
                    };
                }

                switch (columns_obj[ii].赋值类型)
                {
                    case '固定值':
                        let data_arr = [];
                        data_arr[0] = '';

                        let obj_name = columns_obj[ii].对象;
                        let up_name = object_obj[obj_name]['上级对象名称'];
                        let up_value = cond_object_value[up_name];

                        if (up_value == '' || up_value == undefined)
                        {
                            for (let v1 in object_obj[obj_name])
                            {
                                if (v1 == '上级对象名称') continue;

                                for (let v2 in object_obj[obj_name][v1])
                                {
                                    if (v2 != '对象显示值') continue;
                                    for (let v3 in object_obj[obj_name][v1][v2])
                                    {
                                        data_arr.push(object_obj[obj_name][v1][v2][v3]);
                                    }
                                }
                            }
                        }
                        else
                        {
                            for (let v2 in object_obj[obj_name][up_value])
                            {
                                if (v2 != '对象显示值') continue;
                                for (let v3 in object_obj[obj_name][up_value][v2])
                                {
                                    data_arr.push(object_obj[obj_name][up_value][v2][v3]);
                                }
                            }
                        }

                        return {
                            component: 'agSelectCellEditor',
                            params: {
                                //values: object_obj[params.data.列名]
                                values: data_arr
                            },
                        };
                    case '日期':
                        return {
                            component: 'datePicker',
                        };
                    //case '部门':
                        //const popup = new Popup({showImmediately: true});
                }
                break;
            }
        }

        function dept_select(params)
        {
        }

        // 图形窗口按钮
        win_chart_set.footer.events.on('click', function (id)
        {
            if (id == '新增')
            {
                var row = {'行选择':'', '字段名称':'', '坐标轴':'', '图形类型':''};
                chart_grid_obj.push(row);
                chart_grid_options.api.setRowData(chart_grid_obj);
            }
            else if (id == '删除')
            {
                var pos = -1;
                chart_grid_options.api.forEachNode((rowNode, index) => 
                {
                    pos = pos + 1;
                    if (rowNode.isSelected() == true)
                    {
                        chart_grid_obj.splice(pos, 1);
                        chart_grid_options.api.setRowData(chart_grid_obj);
                        //chart_grid_options.api.updateRowData({remove: row});
                    }
                });
            }
            else if (id == '确定')
            {
                chart_draw();
            }
        });

        function chart_draw()
        {
            win_chart_set.hide();

            var chart_type = '';
            var x_axis = ''
            var y_axis = '';

            chart.dataset[0] = [];
            chart_grid_options.api.forEachNode((rowNode, index) => 
            {
                if (chart_type == '') chart_type = rowNode.data['图形类型'];

                switch (rowNode.data['图形类型'])
                {
                    case '饼图':
                        chart.type = 'pie';
                        break;
                    case '折线图':
                        chart.type = 'line';
                        break;
                    case '柱图':
                        chart.type = 'bar';
                        break;
                    case '散点图':
                        chart.type = 'scatter';
                        break;
                    case '雷达图':
                        break;
                }

                console.log('坐标轴', rowNode.data['坐标轴']);

                switch (rowNode.data['坐标轴'])
                {
                    case 'X轴 (下方)':
                        chart.x1_name = rowNode.data['字段名称'];
                        if (x_axis == '') x_axis = rowNode.data['字段名称'];
                        break;
                    case 'X轴 (上方)':
                        chart.x2_name = rowNode.data['字段名称'];
                        if (x_axis == '') x_axis = rowNode.data['字段名称'];
                        break;
                    case 'Y轴 (左侧)':
                        chart.y1_name = rowNode.data['字段名称'];
                        if (y_axis == '') y_axis = rowNode.data['字段名称'];
                        break;
                    case 'Y轴 (右侧)':
                        chart.y2_name = rowNode.data['字段名称'];
                        if (y_axis == '') y_axis = rowNode.data['字段名称'];
                        break;
                }

                chart.dataset[0].push(rowNode.data['字段名称']);
            });

            var pos = 1;
            data_grid_options.api.forEachNodeAfterFilter((rowNode, index) => 
            {
                rowNode.data['字段名称'];
                chart.dataset[pos] = [];

                for (var jj in chart.dataset[0])
                {
                    var fld_name = chart.dataset[0][jj];
                    chart.dataset[pos].push(rowNode.data[fld_name]);
                }

                pos = pos + 1;

                chart.x1_data.push(rowNode.data[chart.x1_name])
                chart.y1_data.push(rowNode.data[chart.y1_name])
            });

            console.log('x1_data', chart.x1_name, chart.x1_data);
            console.log('y1_data', chart.y1_name, chart.y1_data);
            console.log('dataset', chart.dataset);

            foot_chart = '&nbsp&nbsp<b>图型:{' + chart_type + '}, x轴:{' + x_axis + '}, y轴:{' + y_axis + '}</b>';
            $$('footbox').innerHTML = foot_chart;

            var chart_win = echarts.init($$('chart_draw'));
            var data_source = [];
            for (var ii in chart.dataset)
            {
                data_source.push(chart.dataset[ii]);
            }

            console.log('data_source', data_source);

            var chart_option =
            {
                toolbox:
                {
                    show: true,
                    magicType: { type: ['line', 'bar'] },
                    restore: {},
                    saveAsImage: {}
                },
                tooltip:
                {
                    trigger: 'axis',
                    axisPointer: { type:'cross' }
                },
                dataset:
                {
                    source: data_source
                },
                xAxis:
                {
                    name: chart.x1_name,
                    type: 'category',
                    //data: 
                },
                yAxis: {},
                series: [{type:'bar'}]
            };

            chart_win.setOption(chart_option);
        }

    </script>

</body>

</html>
