<!-- v1.1.0.1.202201261720, from office -->
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
    <div id='modifybox' style='width:100%;'>
        <div id='modify_tb'></div>
        <div id='modify_grid' class='ag-theme-alpine' style='width:100%; height:92%; background-color:lightblue;'></div>
    </div>
    <div id='footbox' style='width:100%; height:10px; margin-top:5px; background-color: lightblue;'></div>
    <a id='exp2xls'></a>

    <script type='text/javascript' charset='utf-8'>
        function $$(id)
        {
            return document.getElementById(id);
        }

        $$('databox').style.height = document.documentElement.clientHeight * 0.92 + 'px';
        $$('modifybox').style.height = document.documentElement.clientHeight * 0.92 + 'px';
        $$('footbox').style.height = document.documentElement.clientHeight * 0.033 + 'px';

        $$('databox').style.display = 'block';
        $$('modifybox').style.display = 'none';
        $$('footbox').style.display = 'block';

        $$('footbox').innerHTML = '&nbsp&nbsp<b>条件:{} , 汇总:{} , 平均:{}</b>';

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
        var modify_tb = new dhx.Toolbar('modify_tb', {css:'toobar-class'});
        modify_tb.data.add({id:'返回', type:'button', value:'返回'});
        modify_tb.data.add({type:'separator'});
        modify_tb.data.add({id:'清空', type:'button', value:'清空'});
        modify_tb.data.add({id:'提交', type:'button', value:'提交'});

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

        // 生成modify_grid
        var columns_obj = JSON.parse('<?php echo $columns_json; ?>');
        var columns_arr = Object.values(columns_obj);
        var modify_grid_obj = JSON.parse('<?php echo $modify_value_json; ?>');

        var object_obj = JSON.parse('<?php echo $object_json; ?>');

        const modify_grid_options = 
        {
            columnDefs: 
            [
                {field:'字段名称', width:'120px', resizable:true},
                {field:'字段类型', width:'100px', resizable: true},
                {field:'字段值', width:'300px', resizable:true, editable:true, cellEditorSelector:cellEditorSelector}
            ],
            singleClickEdit: true,
            rowData: modify_grid_obj,

            components:
            {
                datePicker: get_date_picker(),
            }
        };

        new agGrid.Grid($$('modify_grid'), modify_grid_options);

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

                    $$('databox').style.display = 'none';
                    $$('modifybox').style.display = 'block';
                    break;
                case '新增':
                    tb_add_click(id);
                    break;
                case '导出':
                    var href = '<?php base_url(); ?>/Frame/export/<?php echo $func_id; ?>';
                    $$('exp2xls').href = href;
                    $$('exp2xls').click();
                    break;
            }
        });

        // 工具栏点击
        modify_tb.events.on('click', function(id, e) 
        {
            switch (id)
            {
                case '返回':
                    $$('databox').style.display = 'block';
                    $$('modifybox').style.display = 'none';
                    break;
                case '清空':
                    modify_grid_obj = JSON.parse('<?php echo $modify_value_json; ?>');
                    modify_grid_options.api.setRowData(modify_grid_obj);
                    break;
                case '提交':
                    tb_submit_click(id);
                    break;
            }
        });

        function tb_submit_click(id)
        {

            modify_grid_options.api.stopEditing();

            var modify_arr = [];

            modify_grid_options.api.forEachNode((rowNode, index) => 
            {
                console.log('rownode=', rowNode);
                if (rowNode.data['字段值'] != '')
                {
                    var val = {};
                    val[rowNode.data['字段名称']] = rowNode.data['字段值'];
                    modify_arr.push(val);
                }
            });

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

            var val = {};
            val[key] = key_values;
            modify_arr.push(val);

            dhx.ajax.post('<?php base_url(); ?>/Frame/update_row/<?php echo $func_id; ?>', modify_arr).then(function (data)
            {
                // 更改data_grid的记录(后期改变背景颜色)
                var rows = data_grid_options.api.getSelectedRows();

                for (var ii in rows)
                {
                    for (var jj in modify_arr)
                    {
                        for (var kk in modify_arr[jj])
                        {
                            var id = kk;
                            var vv = modify_arr[jj][kk];
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

        function cellEditorSelector(params)
        {
            console.log('params', params);
            var col_name = params.data.字段名称;

            for (var ii in columns_obj)
            {
                if (columns_obj[ii].列名 != col_name) continue;
                switch (columns_obj[ii].赋值类型)
                {
                    case '下拉':
                        return {
                            component: 'agSelectCellEditor',
                            params: {
                                values: object_obj[params.data.字段名称]
                            },
                        };
                    case '日期':
                        return {
                            component: 'datePicker',
                            /*
                            params: {
                                values: object_obj[params.data.字段名称]
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