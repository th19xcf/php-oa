<!-- v4.1.1.1.202207140025, from home -->
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
            data_tb.data.add({id:'修改', type:'button', value:'修改'});
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
        data_tb.data.add({id:'导出', type:'button', value:'导出'});

        // 生成修改新增用工具栏
        var update_tb = new dhx.Toolbar('update_tb', {css:'toobar-class'});
        //update_tb.data.add({id:'modify', type:'title', value:'修改菜单 --> '});
        //update_tb.data.add({id:'add', type:'title', value:'新增菜单 --> '});
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
        chart_tb.data.add({id:'设置', type:'button', value:'设置'});

        // 生成data_grid
        var data_page = 500;
        var data_columns_obj = JSON.parse('<?php echo $data_col_json; ?>');

        var data_columns_arr = []; // 数据表使用
        data_columns_arr = Object.values(data_columns_obj);

        var data_grid_obj = JSON.parse('<?php echo $data_value_json; ?>');

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
                if (columns_obj[jj].显示异常 != '' && columns_obj[jj].类型 == '数值')
                {
                    data_columns_arr[ii].cellStyle = value_cell_style;
                }
                else if (columns_obj[jj].显示异常 != '' && columns_obj[jj].类型 == '字符')
                {
                    data_columns_arr[ii].cellStyle = str_cell_style;
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
            pagination: true,
            localeText: AG_GRID_LOCALE_CN
        };

        new agGrid.Grid($$('data_grid'), data_grid_options);

        data_grid_options.onGridReady = data_grid_ready;
        function data_grid_ready(event)
        {
            data_grid_options.api.paginationSetPageSize(Number(500));
            console.log('datagrid ready');
        }

        // 生成update_grid
        var update_grid_obj = JSON.parse('<?php echo $update_value_json; ?>');
        var object_obj = JSON.parse('<?php echo $object_json; ?>');

        var column_name_arr = [];
        for (var ii in columns_arr)
        {
            column_name_arr.push(columns_arr[ii]['列名']);
        }

        //console.log('columns_obj', columns_obj);
        //console.log('cols_arr', columns_arr);
        //console.log('col_name_arr', column_name_arr);

        const update_grid_options = 
        {
            columnDefs: 
            [
                {field:'列名'},
                {field:'字段名', hide:true},
                {field:'列类型'},
                {field:'取值', width:200, cellEditorSelector:cellEditorSelector}
            ],
            defaultColDef: 
            {
                width: 120,
                resizable: true,
                editable: (params) =>
                {
                    // 根据配置判断是否可以修改
                    var col_name = params.data.列名;

                    for (var ii in columns_obj)
                    {
                        if (columns_obj[ii].列名 != col_name) continue;
                        return (columns_obj[ii].可修改 == '1' && columns_obj[ii].对应表名 != '') ? true : false;
                    }

                    return false;
                }
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
                    cellEditor: 'agSelectCellEditor',
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
                case '修改':
                    var rows = data_grid_options.api.getSelectedRows();
                    if (rows.length == 0)
                    {
                        alert('请先选择要修改的记录');
                        break;
                    }

                    foot_upkeep = '';
                    for (var ii in rows)
                    {
                        if (foot_upkeep != '') foot_upkeep = foot_upkeep + ',';
                        foot_upkeep = foot_upkeep + rows[ii][tip_column];
                        $$('footbox').innerHTML = '&nbsp&nbsp<b>选定记录:{' + foot_upkeep + '}</b>';
                    }
                    if (update_flag != 'modify')
                    {
                        // 清空
                        update_grid_obj = JSON.parse('<?php echo $update_value_json; ?>');
                        update_grid_options.api.setRowData(update_grid_obj);
                    }

                    update_flag = 'modify';
                    div_block('updatebox');
                    break;
                case '新增':
                    if (update_flag != 'add')
                    {
                        // 清空
                        update_grid_obj = JSON.parse('<?php echo $update_value_json; ?>');
                        update_grid_options.api.setRowData(update_grid_obj);
                    }

                    update_flag = 'add';
                    div_block('updatebox');
                    break;
                case '删除':
                    var rows = data_grid_options.api.getSelectedRows();
                    if (rows.length == 0)
                    {
                        alert('请先选择要删除的记录');
                        break;
                    }
                    break;
                case '数据钻取':
                    var rows = data_grid_options.api.getSelectedRows();
                    if (rows.length == 0)
                    {
                        alert('请先选择要修改的记录');
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

                    for (var ii in nl_arr)
                    {
                        send_obj[nl_arr[ii]] = rows[0][nl_arr[ii]];
                    }

                    send_str = JSON.stringify(send_obj);
                    //console.log('send=', send_obj, send_str);

                    parent.window.goto('<?php echo $next_func_id; ?>','钻取-'+'<?php echo $next_func_name; ?>','Frame/init/<?php echo $next_func_id; ?>/'+send_str);
                    break;
                case '导入':
                    parent.window.goto('<?php echo $import_func_id; ?>','导入-'+'<?php echo $import_func_name; ?>','Upload/init/<?php echo $import_func_id; ?>');
                    break;
                case '导出':
                    var href = '<?php base_url(); ?>/Frame/export/<?php echo $func_id; ?>';
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

        // 工具栏点击
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

        // 条件栏点击
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
                    condition_submit(id);
                    break;
            }
        });

        // 图形工具栏点击
        chart_tb.events.on('click', function(id, e) 
        {
            switch (id)
            {
                case '返回':
                    div_block('databox');
                    $$('footbox').innerHTML = foot_data;
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

        function condition_submit(id)
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
                cond.col_name = rowNode.data['字段名'];
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

            console.log('cond=', cond_arr);
            dhx.ajax.post('<?php base_url(); ?>/Frame/set_condition/<?php echo $func_id; ?>', cond_arr).then(function (data)
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

        function update_submit(id)
        {
            var update_arr = [];
            var add_arr = [];

            update_grid_options.api.stopEditing();
            update_grid_options.api.forEachNode((rowNode, index) => 
            {
                var col = new ColumnInfo();
                col.col_name = rowNode.data['列名'];
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

        function value_sort(valueA, valueB, nodeA, nodeB, isInverted)
        {
            return valueA - valueB;
        }

        function value_cell_style(params)
        {
            if (params.value < 0)
            {
                return {'color':'red','font-weight':'bold'};
            }
            return null;
        }

        function str_cell_style(params)
        {
            //console.log(params);
            var str = params.value;
            if (str.indexOf('请补充') != -1)
            {
                return {'color':'green','font-weight':'bold'};
            }
            return null;
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
                        };
                }
                break;
            }
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
                win_chart_set.hide();

                chart.dataset[0] = [];
                chart_grid_options.api.forEachNode((rowNode, index) => 
                {
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
                            break;
                        case 'X轴 (上方)':
                            chart.x2_name = rowNode.data['字段名称'];
                            break;
                        case 'Y轴 (左侧)':
                            chart.y1_name = rowNode.data['字段名称'];
                            break;
                        case 'Y轴 (右侧)':
                            chart.y2_name = rowNode.data['字段名称'];
                            break;
                    }

                    chart.dataset[0].push(rowNode.data['字段名称']);
                });

                var pos = 1;
                for (var ii in data_grid_obj)
                {
                    chart.dataset[pos] = [];

                    for (var jj in chart.dataset[0])
                    {
                        var fld_name = chart.dataset[0][jj];
                        chart.dataset[pos].push(data_grid_obj[ii][fld_name]);
                    }

                    pos = pos + 1;

                    chart.x1_data.push(data_grid_obj[ii][chart.x1_name])
                    chart.y1_data.push(data_grid_obj[ii][chart.y1_name])
                }

                console.log('x1_data', chart.x1_name, chart.x1_data);
                console.log('y1_data', chart.y1_name, chart.y1_data);
                console.log('dataset', chart.dataset);

                chart_draw();
            }
        });

        function chart_draw()
        {
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