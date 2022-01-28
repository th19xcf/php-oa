<!-- v1.2.1.1.202201281010, from office -->
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
    <div id='footbox' style='width:100%; height:10px; margin-top:5px; background-color: lightblue;'></div>
    <a id='exp2xls'></a>

    <script type='text/javascript' charset='utf-8'>
        function $$(id)
        {
            return document.getElementById(id);
        }

        $$('databox').style.height = document.documentElement.clientHeight * 0.92 + 'px';
        $$('updatebox').style.height = document.documentElement.clientHeight * 0.92 + 'px';
        $$('footbox').style.height = document.documentElement.clientHeight * 0.033 + 'px';

        $$('databox').style.display = 'block';
        $$('updatebox').style.display = 'none';
        $$('footbox').style.display = 'block';

        $$('footbox').innerHTML = '&nbsp&nbsp<b>条件:{} , 汇总:{} , 平均:{}</b>';

        function ColumnInfo()
        {
            this.col_name = '';
            this.fld_name = '';
            this.type = '';
            this.value = '';
        }

        var update_flag = '';  // modify或add

        // 生成主菜单栏
        var data_tb = new dhx.Toolbar('data_tb', {css:'toobar-class'});
        //data_tb.data.add({id:'名称', type:'title', value:'主菜单-->'});
        data_tb.data.add({id:'刷新', type:'button', value:'刷新'});
        data_tb.data.add({id:'分页', type:'button', value:'分页'});
        data_tb.data.add({type:'separator'});
        data_tb.data.add({id:'修改', type:'button', value:'修改'});
        data_tb.data.add({id:'新增', type:'button', value:'新增'});
        data_tb.data.add({type:'spacer'});
        data_tb.data.add({id:'导出', type:'button', value:'导出'});

        // 生成修改新增用菜单栏
        var update_tb = new dhx.Toolbar('update_tb', {css:'toobar-class'});
        update_tb.data.add({id:'返回', type:'button', value:'返回'});
        update_tb.data.add({type:'separator'});
        update_tb.data.add({id:'清空', type:'button', value:'清空'});
        update_tb.data.add({id:'提交', type:'button', value:'提交'});

        // 生成data_grid
        var data_columns_obj = JSON.parse('<?php echo $data_col_json; ?>');

        var data_columns_arr = []; // 数据表使用
        data_columns_arr = Object.values(data_columns_obj);

        var data_grid_obj = JSON.parse('<?php echo $data_value_json; ?>');

        const data_grid_options = 
        {
            columnDefs: data_columns_arr,
            rowData: data_grid_obj,
            rowSelection: 'multiple',
            pagination: true
        };

        new agGrid.Grid($$('data_grid'), data_grid_options);

        // 生成update_grid
        var columns_obj = JSON.parse('<?php echo $columns_json; ?>');
        var columns_arr = Object.values(columns_obj);
        var update_grid_obj = JSON.parse('<?php echo $update_value_json; ?>');

        var object_obj = JSON.parse('<?php echo $object_json; ?>');

        const update_grid_options = 
        {
            columnDefs: 
            [
                {field:'列名', width:'120px', resizable:true},
                {field:'字段名', width:'100px', resizable: true},
                {field:'列类型', width:'100px', resizable: true},
                {field:'取值', width:'300px', resizable:true, editable:true, cellEditorSelector:cellEditorSelector}
            ],
            singleClickEdit: true,
            rowData: update_grid_obj,

            components:
            {
                datePicker: get_date_picker(),
            }
        };

        new agGrid.Grid($$('update_grid'), update_grid_options);

        // 工具栏点击
        data_tb.events.on('click', function(id, e) 
        {
            switch (id)
            {
                case '刷新':
                    window.location.reload();
                    break;
                case '分页':
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
                    break;
                case '清空':
                    update_grid_obj = JSON.parse('<?php echo $update_value_json; ?>');
                    update_grid_options.api.setRowData(update_grid_obj);
                    break;
                case '提交':
                    tb_submit_click(id);
                    break;
            }
        });

        function tb_submit_click(id)
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
                    console.log('新增记录成功');
                }).catch(function (err)
                {
                    console.log('新增记录错误, ' + " " + err.statusText);
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
                    console.log('status' + " " + err.statusText);
                });
            }
        }

        function cellEditorSelector(params)
        {
            console.log('params', params);
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