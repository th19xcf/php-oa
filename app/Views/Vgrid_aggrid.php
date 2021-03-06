<!-- v4.1.2.1.202207171030, from home -->
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

        foot_data = '&nbsp&nbsp<b>??????:{}, ??????:{}, ??????:{}, ??????:{}, ??????:{}, ??????:{}, ??????:{}</b>';
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

        // footbox??????
        var foot_data = '';
        var foot_upkeep = '';
        var foot_chart = '';

        var back_where = '<?php echo $back_where; ?>';
        var back_group = '<?php echo $back_group; ?>';
        var tip_column = '<?php echo $tip_column; ?>';

        foot_data = '&nbsp&nbsp<b>??????:{' + back_where + '}, ??????:{' + back_group + '}, ??????:{}, ??????:{}, ??????:{}, ??????:{}</b>';
        $$('footbox').innerHTML = foot_data;

        // ????????????
        var columns_obj = JSON.parse('<?php echo $columns_json; ?>');
        var columns_arr = Object.values(columns_obj);

        // ???????????????
        var tb_obj = JSON.parse('<?php echo $toolbar_json; ?>');

        var update_flag = '';  // modify???add

        // ??????????????????
        var data_tb = new dhx.Toolbar('data_tb', {css:'toobar-class'});
        data_tb.data.add({id:'??????', type:'button', value:'??????'});
        data_tb.data.add({id:'????????????', type:'button', value:'????????????'});
        data_tb.data.add({id:'????????????', type:'button', value:'????????????'});
        if (tb_obj['????????????'] == true)
        {
            data_tb.data.add({id:'????????????', type:'button', value:'????????????'});
        }
        data_tb.data.add({id:'??????', type:'button', value:'??????'});
        data_tb.data.add({type:'separator'});
        if (tb_obj['????????????'] == true)
        {
            data_tb.data.add({id:'??????', type:'button', value:'??????'});
        }
        if (tb_obj['????????????'] == true)
        {
            data_tb.data.add({id:'??????', type:'button', value:'??????'});
        }
        if (tb_obj['????????????'] == true)
        {
            data_tb.data.add({id:'??????', type:'button', value:'??????'});
        }
        data_tb.data.add({type:'separator'});
        data_tb.data.add({id:'title', type:'title', value:'??????'});
        data_tb.data.add(
        {
            id: '??????',
            type: 'selectButton',
            value: '500',
            items: [{id:'500',value:'500'},{id:'1000',value:'1000'},{id:'2000',value:'2000'}]
        });
        data_tb.data.add({type:'spacer'});
        if (tb_obj['????????????'] == true)
        {
            data_tb.data.add({id:'??????', type:'button', value:'??????'});
        }
        data_tb.data.add({id:'??????', type:'button', value:'??????'});

        // ??????????????????????????????
        var update_tb = new dhx.Toolbar('update_tb', {css:'toobar-class'});
        //update_tb.data.add({id:'modify', type:'title', value:'???????????? --> '});
        //update_tb.data.add({id:'add', type:'title', value:'???????????? --> '});
        update_tb.data.add({id:'??????', type:'button', value:'??????'});
        update_tb.data.add({type:'separator'});
        update_tb.data.add({id:'??????', type:'button', value:'??????'});
        update_tb.data.add({id:'??????', type:'button', value:'??????'});

        // ??????????????????????????????
        var cond_tb = new dhx.Toolbar('cond_tb', {css:'toobar-class'});
        cond_tb.data.add({id:'??????', type:'button', value:'??????'});
        cond_tb.data.add({type:'separator'});
        cond_tb.data.add({id:'??????', type:'button', value:'??????'});
        cond_tb.data.add({id:'??????', type:'button', value:'??????'});

        // ????????????????????????
        var chart_tb = new dhx.Toolbar('chart_tb', {css:'toobar-class'});
        chart_tb.data.add({id:'??????', type:'button', value:'??????'});
        chart_tb.data.add({type:'separator'});
        chart_tb.data.add({id:'??????', type:'button', value:'??????'});
        chart_tb.data.add({id:'??????', type:'button', value:'??????'});

        // ??????data_grid
        var data_page = 500;
        var data_columns_obj = JSON.parse('<?php echo $data_col_json; ?>');

        var data_columns_arr = []; // ???????????????
        data_columns_arr = Object.values(data_columns_obj);

        var data_grid_obj = JSON.parse('<?php echo $data_value_json; ?>');

        // ????????????,??????cell??????
        for (var ii in data_columns_arr)
        {
            for (var jj in columns_obj)
            {
                if (columns_obj[jj].?????? != data_columns_arr[ii].field) continue;
                
                if (columns_obj[jj].?????? == '??????')
                {
                    data_columns_arr[ii].comparator = value_sort;
                }
                if (columns_obj[jj].???????????? != '' && columns_obj[jj].?????? == '??????')
                {
                    data_columns_arr[ii].cellStyle = value_cell_style;
                }
                else if (columns_obj[jj].???????????? != '' && columns_obj[jj].?????? == '??????')
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

        // ??????update_grid
        var update_grid_obj = JSON.parse('<?php echo $update_value_json; ?>');
        var object_obj = JSON.parse('<?php echo $object_json; ?>');

        var column_name_arr = [];
        for (var ii in columns_arr)
        {
            column_name_arr.push(columns_arr[ii]['??????']);
        }

        //console.log('columns_obj', columns_obj);
        //console.log('cols_arr', columns_arr);
        //console.log('col_name_arr', column_name_arr);

        const update_grid_options = 
        {
            columnDefs: 
            [
                {field:'??????'},
                {field:'?????????', hide:true},
                {field:'?????????'},
                {field:'??????', width:200, cellEditorSelector:cellEditorSelector}
            ],
            defaultColDef: 
            {
                width: 120,
                resizable: true,
                editable: (params) =>
                {
                    // ????????????????????????????????????
                    var col_name = params.data.??????;

                    for (var ii in columns_obj)
                    {
                        if (columns_obj[ii].?????? != col_name) continue;
                        return (columns_obj[ii].????????? == '1' && columns_obj[ii].???????????? != '') ? true : false;
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
                {field:'??????', width:120, editable:false},
                {field:'?????????', width:120, editable:false},
                {field:'?????????', editable:false},
                {
                    field:'??????',
                    cellEditor: 'agSelectCellEditor',
                    cellEditorParams: 
                    {
                        values: ['','???'],
                    },
                },
                {
                    field:'??????1',
                    cellEditor: 'agSelectCellEditor',
                    cellEditorParams: 
                    {
                        values: ['','??????','??????','??????','????????????','????????????','?????????','??????','?????????'],
                    },
                },
                {field:'??????1', width:180, cellEditorSelector:cellEditorSelector},
                {
                    field:'????????????', 
                    cellEditor: 'agSelectCellEditor',
                    cellEditorParams: 
                    {
                        values: ['', '??????', '??????'],
                    },
                },
                {
                    field:'??????2',
                    cellEditor: 'agSelectCellEditor',
                    cellEditorParams: 
                    {
                        values: ['','??????','??????','??????','????????????','????????????','?????????','??????','?????????'],
                    },
                },
                {field:'??????2', width:180, cellEditorSelector:cellEditorSelector},
                {
                    field:'????????????',
                    cellEditor: 'agSelectCellEditor',
                    cellEditorParams: 
                    {
                        values: ['', '??????', '??????', '??????', '??????', '??????'],
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

        // ????????????
        var chart_grid_obj = [];
        var win_chart_set = new dhx.Window(
        {
            title: '????????????????????????',
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
            id: '??????',
            value: '??????',
            view: 'flat',
            size: 'medium',
            color: 'primary',
        });

        win_chart_set.footer.data.add(
        {
            type: 'button',
            id: '??????',
            value: '??????',
            view: 'flat',
            size: 'medium',
            color: 'primary',
        });

        win_chart_set.footer.data.add(
        {
            type: 'button',
            id: '??????',
            value: '??????',
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
                    field: '?????????',
                    width: 100,
                    checkboxSelection: true,
                },
                {
                    field: '????????????',
                    width: 150,
                    cellEditor: 'agSelectCellEditor',
                    cellEditorParams: 
                    {
                        values: column_name_arr,
                    },
                },
                {
                    field: '?????????',
                    width: 120,
                    cellEditor: 'agSelectCellEditor',
                    cellEditorParams: 
                    {
                        values: ['X??? (??????)','X??? (??????)','Y??? (??????)','Y??? (??????)'],
                    },
                },
                {
                    field: '????????????',
                    width: 120,
                    cellEditor: 'agSelectCellEditor',
                    cellEditorParams:
                    {
                        values: ['??????','?????????','??????', '?????????', '?????????'],
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
                {'?????????':'', '????????????':'', '?????????':'', '????????????':''},
                {'?????????':'', '????????????':'', '?????????':'', '????????????':''}
            ]
            */
        };

        // ???????????????
        data_tb.events.on('click', function(id, e)
        {
            switch (id)
            {
                case '??????':
                    window.location.reload();
                    break;
                case '????????????':
                    tb_select_field();
                    break;
                case '????????????':
                    div_block('conditionbox');
                    break;
                case '??????':
                    div_block('chartbox');
                    tb_chart();
                    break;
                case '??????':
                    var rows = data_grid_options.api.getSelectedRows();
                    if (rows.length == 0)
                    {
                        alert('??????????????????????????????');
                        break;
                    }

                    foot_upkeep = '';
                    for (var ii in rows)
                    {
                        if (foot_upkeep != '') foot_upkeep = foot_upkeep + ',';
                        foot_upkeep = foot_upkeep + rows[ii][tip_column];
                        $$('footbox').innerHTML = '&nbsp&nbsp<b>????????????:{' + foot_upkeep + '}</b>';
                    }
                    if (update_flag != 'modify')
                    {
                        // ??????
                        update_grid_obj = JSON.parse('<?php echo $update_value_json; ?>');
                        update_grid_options.api.setRowData(update_grid_obj);
                    }

                    update_flag = 'modify';
                    div_block('updatebox');
                    break;
                case '??????':
                    if (update_flag != 'add')
                    {
                        // ??????
                        update_grid_obj = JSON.parse('<?php echo $update_value_json; ?>');
                        update_grid_options.api.setRowData(update_grid_obj);
                    }

                    update_flag = 'add';
                    div_block('updatebox');
                    break;
                case '??????':
                    var rows = data_grid_options.api.getSelectedRows();
                    if (rows.length == 0)
                    {
                        alert('??????????????????????????????');
                        break;
                    }
                    break;
                case '????????????':
                    var rows = data_grid_options.api.getSelectedRows();
                    if (rows.length == 0)
                    {
                        alert('??????????????????????????????');
                        break;
                    }
                    if (rows.length > 1)
                    {
                        alert('????????????1?????????');
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

                    parent.window.goto('<?php echo $next_func_id; ?>','??????-'+'<?php echo $next_func_name; ?>','Frame/init/<?php echo $next_func_id; ?>/'+send_str);
                    break;
                case '??????':
                    parent.window.goto('<?php echo $import_func_id; ?>','??????-'+'<?php echo $import_func_name; ?>','Upload/init/<?php echo $import_func_id; ?>');
                    break;
                case '??????':
                    var href = '<?php base_url(); ?>/Frame/export/<?php echo $func_id; ?>';
                    $$('exp2xls').href = href;
                    $$('exp2xls').click();
                    break;
            }
        });

        data_tb.events.on('change', function(id,status,updatedItem)
        {
            if (id == '??????' && data_page != updatedItem['value'])
            {
                data_page =  updatedItem['value'];
                data_grid_options.api.paginationSetPageSize(Number(data_page));
            }
        });

        // ???????????????
        update_tb.events.on('click', function(id, e) 
        {
            switch (id)
            {
                case '??????':
                    div_block('databox');
                    $$('footbox').innerHTML = foot_data;
                    break;
                case '??????':
                    update_grid_obj = JSON.parse('<?php echo $update_value_json; ?>');
                    update_grid_options.api.setRowData(update_grid_obj);
                    break;
                case '??????':
                    update_submit(id);
                    break;
            }
        });

        // ???????????????
        cond_tb.events.on('click', function(id, e) 
        {
            switch (id)
            {
                case '??????':
                    div_block('databox');
                    $$('footbox').innerHTML = foot_data;
                    break;
                case '??????':
                    cond_grid_obj = JSON.parse('<?php echo $cond_value_json; ?>');
                    cond_grid_options.api.setRowData(cond_grid_obj);
                    break;
                case '??????':
                    condition_submit(id);
                    break;
            }
        });

        // ?????????????????????
        chart_tb.events.on('click', function(id, e) 
        {
            switch (id)
            {
                case '??????':
                    div_block('databox');
                    $$('footbox').innerHTML = foot_data;
                    break;
                case '??????':
                    chart_draw();
                    break;
                case '??????':
                    tb_chart();
                    break;
            }
        });

        // ????????????????????????
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
                title: '??????????????????',
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
                if (rowNode.data['????????????']=='??????' && rowNode.data['?????????']!='??????')
                {
                    alert("'" + rowNode.data['?????????'] + "'" + '??????????????????,????????????,???????????????');
                    return;
                }
                if (rowNode.data['????????????']=='??????' && rowNode.data['?????????']!='??????')
                {
                    alert("'" + rowNode.data['?????????'] + "'" + '??????????????????,????????????,???????????????');
                    return;
                }
                if (rowNode.data['??????1']!='' && rowNode.data['??????1']=='')
                {
                    alert("'" + rowNode.data['?????????'] + "'" + '??????1,??????');
                    return;
                }
                if (rowNode.data['??????2']!='' && rowNode.data['??????2']=='')
                {
                    alert("'" + rowNode.data['?????????'] + "'" + '??????2,??????');
                    return;
                }
                if (rowNode.data['??????1']!='' && rowNode.data['??????2']!='' && rowNode.data['????????????']=='')
                {
                    alert("'" + rowNode.data['?????????'] + "'" + '????????????,??????');
                    return;
                }

                var ajax = false;
                var cond = new CondInfo();
                cond.col_name = rowNode.data['?????????'];
                cond.fld_name = rowNode.data['?????????'];
                cond.type = rowNode.data['?????????'];
                cond.cond_1 = rowNode.data['??????1'];
                cond.arg_1 = rowNode.data['??????1'];
                cond.and_or = rowNode.data['????????????'];
                cond.cond_2 = rowNode.data['??????2'];
                cond.arg_2 = rowNode.data['??????2'];
                cond.sum_avg = rowNode.data['????????????'];

                if (rowNode.data['??????'] == '???')
                {
                    cond.group = '1';
                    group_flag = true;
                    ajax = true;
                }
                if (rowNode.data['????????????'] != '')
                {
                    cond.sum_avg = rowNode.data['????????????'];
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
                    case '??????':
                        if (sum_str != '') sum_str = sum_str + ',';
                        sum_str = sum_str + cond.col_name;
                        break;
                    case '??????':
                        if (average_str != '') average_str = average_str + ',';
                        average_str = average_str + cond.col_name;
                        break;
                    case '??????':
                        if (max_str != '') max_str = max_str + ',';
                        max_str = max_str + cond.col_name;
                        break;
                    case '??????':
                        if (min_str != '') min_str = min_str + ',';
                        min_str = min_str + cond.col_name;
                        break;
                    case '??????':
                        if (count_str != '') count_str = count_str + ',';
                        count_str = count_str + cond.col_name;
                        break;
                }

                if (ajax == true) cond_arr.push(cond);
            });

            if (sum_flag==true && group_flag==false)
            {
                alert('???????????????, ????????????????????????');
                return;
            }

            if (average_flag==true && group_flag==false)
            {
                alert('???????????????, ????????????????????????');
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

                foot_data = '&nbsp&nbsp<b>??????:{' + disp_where + '} , ??????:{' + disp_group + '} , ??????:{' + sum_str + '}, ??????:{' + average_str + '}, ??????:{' + max_str + '}, ??????:{' + min_str + '}, ??????:{' + count_str + '}</b>';
                $$('footbox').innerHTML = foot_data;
            }).catch(function (err)
            {
                alert('??????????????????, ' + " " + err.statusText);
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
                col.col_name = rowNode.data['??????'];
                col.fld_name = rowNode.data['?????????'];
                col.type = rowNode.data['?????????'];
                col.value = rowNode.data['??????'];

                add_arr.push(col);

                if (rowNode.data['??????'] != '')
                {
                    update_arr.push(col);
                }
            });

            if (update_flag == 'add')
            {
                dhx.ajax.post('<?php base_url(); ?>/Frame/add_row/<?php echo $func_id; ?>', add_arr).then(function (data)
                {
                    alert('??????????????????');
                }).catch(function (err)
                {
                    alert('??????????????????, ' + " " + err.statusText);
                });
            }

            else if (update_flag == 'modify')
            {
                // ???????????????
                var rows = data_grid_options.api.getSelectedRows();

                var key = '<?php echo $primary_key; ?>';
                var key_values = '';

                for (var ii in rows)
                {
                    if (key_values == '')
                    {
                        key_values = data_grid_obj[rows[ii].??????-1][key];
                    }
                    else
                    {
                        key_values = key_values + ',' + data_grid_obj[rows[ii].??????-1][key];
                    }
                }

                var col = new ColumnInfo();
                col.col_name = key;
                col.fld_name = key;
                col.value = key_values;

                update_arr.push(col);

                dhx.ajax.post('<?php base_url(); ?>/Frame/update_row/<?php echo $func_id; ?>', update_arr).then(function (data)
                {
                    // ??????data_grid?????????(????????????????????????)
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
                                data_grid_obj[rows[ii].??????-1][id] = vv;
                            }
                        }
                    }

                    data_grid_options.api.refreshCells();

                    alert('??????????????????');
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
            var str = params.value;
            if (str.indexOf('?????????') != -1 || str.indexOf('?????????') != -1)
            {
                return {'color':'green','font-weight':'bold'};
            }
            return null;
        }

        function cellEditorSelector(params)
        {
            var col_name = params.data.??????;

            for (var ii in columns_obj)
            {
                if (columns_obj[ii].?????? != col_name) continue;
                switch (columns_obj[ii].????????????)
                {
                    case '??????':
                        return {
                            component: 'agSelectCellEditor',
                            params: {
                                values: object_obj[params.data.??????]
                            },
                        };
                    case '??????':
                        return {
                            component: 'datePicker',
                        };
                }
                break;
            }
        }

        // ??????????????????
        win_chart_set.footer.events.on('click', function (id)
        {
            if (id == '??????')
            {
                var row = {'?????????':'', '????????????':'', '?????????':'', '????????????':''};
                chart_grid_obj.push(row);
                chart_grid_options.api.setRowData(chart_grid_obj);
            }
            else if (id == '??????')
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
            else if (id == '??????')
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
                if (chart_type == '') chart_type = rowNode.data['????????????'];

                switch (rowNode.data['????????????'])
                {
                    case '??????':
                        chart.type = 'pie';
                        break;
                    case '?????????':
                        chart.type = 'line';
                        break;
                    case '??????':
                        chart.type = 'bar';
                        break;
                    case '?????????':
                        chart.type = 'scatter';
                        break;
                    case '?????????':
                        break;
                }

                console.log('?????????', rowNode.data['?????????']);

                switch (rowNode.data['?????????'])
                {
                    case 'X??? (??????)':
                        chart.x1_name = rowNode.data['????????????'];
                        if (x_axis == '') x_axis = rowNode.data['????????????'];
                        break;
                    case 'X??? (??????)':
                        chart.x2_name = rowNode.data['????????????'];
                        if (x_axis == '') x_axis = rowNode.data['????????????'];
                        break;
                    case 'Y??? (??????)':
                        chart.y1_name = rowNode.data['????????????'];
                        if (y_axis == '') y_axis = rowNode.data['????????????'];
                        break;
                    case 'Y??? (??????)':
                        chart.y2_name = rowNode.data['????????????'];
                        if (y_axis == '') y_axis = rowNode.data['????????????'];
                        break;
                }

                chart.dataset[0].push(rowNode.data['????????????']);
            });

            var pos = 1;
            data_grid_options.api.forEachNodeAfterFilter((rowNode, index) => 
            {
                rowNode.data['????????????'];
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

            foot_chart = '&nbsp&nbsp<b>??????:{' + chart_type + '}, x???:{' + x_axis + '}, y???:{' + y_axis + '}</b>';
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