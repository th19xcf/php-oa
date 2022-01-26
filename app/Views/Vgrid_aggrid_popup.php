<!-- v1.1.1.0.202201131650, from office -->
<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8'>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>ag-grid</title>

    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/ag-grid/dist/styles/ag-grid.css'>
    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/ag-grid/dist/styles/ag-theme-alpine.css'>
    <script src='<?php base_url(); ?>/ag-grid/dist/ag-grid-locale-cn.js'></script>
    <script src='<?php base_url(); ?>/ag-grid/dist/ag-grid-community.noStyle.js'></script>

    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/dhtmlx/codebase/suite.css'>
    <script src='<?php base_url(); ?>/dhtmlx/codebase/suite.js'></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css"/>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css"/>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap-theme.min.css"/>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.12.1/jquery.min.js">
	</script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js">
	</script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.min.js">
	</script>
</head>

<body>
    <div id='toolbarbox'></div>
    <div id='gridbox' class='ag-theme-alpine' style='width:100%; height:600px; background-color:lightblue;'></div>
    <div id='footbox' style='width:100%; height:10px; margin-top:5px; background-color: lightblue;'></div>
    <a id='exp2xls'></a>

    <script type='text/javascript' charset='utf-8'>
        function $$(id)
        {
            return document.getElementById(id);
        }

        $$('gridbox').style.height = document.documentElement.clientHeight * 0.85 + 'px';
        $$('footbox').style.height = document.documentElement.clientHeight * 0.033 + 'px';
        $$('footbox').innerHTML = '&nbsp&nbsp<b>条件:{} , 汇总:{} , 平均:{}</b>';

        // 生成主菜单栏
        var main_tb = new dhx.Toolbar('toolbarbox', {css:'toobar-class'});
        //main_tb.data.add({id:'名称', type:'title', value:'主菜单-->'});
        main_tb.data.add({id:'刷新', type:'button', value:'刷新'});
        main_tb.data.add({id:'分页', type:'button', value:'分页'});
        main_tb.data.add({type:'separator'});
        main_tb.data.add({id:'修改', type:'button', value:'修改'});
        main_tb.data.add({id:'新增', type:'button', value:'新增'});
        main_tb.data.add({type:'spacer'});
        main_tb.data.add({id:'导出', type:'button', value:'导出'});

        var data_columns_obj = JSON.parse('<?php echo $data_col_json; ?>');
        //console.log('data_column_obj', data_columns_obj);

        var data_columns_arr = []; // 数据表使用
        data_columns_arr = Object.values(data_columns_obj);
        //console.log('data_column_arr', data_columns_arr);

        var data_grid_obj = JSON.parse('<?php echo $data_value_json; ?>');
        //console.log('data_grid_obj', data_grid_obj);

        // let the grid know which columns and what data to use
        const data_grid_options = 
        {
            columnDefs: data_columns_arr,
            rowData: data_grid_obj,
            rowSelection: 'multiple',
            pagination: true,
            localeText: AG_GRID_LOCALE_CN,
        };

        // lookup the container we want the Grid to use
        //const eGridDiv = document.querySelector('#gridbox');

        // create the grid passing in the div to use together with the columns & data we want to use
        new agGrid.Grid($$('gridbox'), data_grid_options);

        var columns_obj = JSON.parse('<?php echo $columns_json; ?>');
        var columns_arr = Object.values(columns_obj);
        var modify_grid_obj = JSON.parse('<?php echo $modify_value_json; ?>');
        //console.log('columns_obj', columns_obj);
        //console.log('columns_arr', columns_arr);
        //console.log('name', columns_obj[0]);

        var object_obj = JSON.parse('<?php echo $object_json; ?>');
        //console.log('object_obj', object_obj);

        // 修改及新增记录使用
        const modify_grid_options = 
        {
            columnDefs: 
            [
                {field:'字段名称', width:'120px', resizable:true},
                {field:'字段类型', width:'100px', resizable: true},
                {field:'字段值', width:'300px', resizable:true, editable:true, cellEditorSelector:cellEditorSelector}
            ],
            //rowSelection: 'multiple',
            singleClickEdit: true,
            rowData: modify_grid_obj,

            components:
            {
                datePicker: get_date_picker(),
            },
        };

        function get_date_picker()
        {
            //console.log('into get_date_picker();');

            // function to act as a class
            function Datepicker() {}

            // gets called once before the renderer is used
            Datepicker.prototype.init = function (params)
            {
                // create the cell
                this.eInput = document.createElement('input');
                this.eInput.value = params.value;
                //this.eInput.classList.add('ag-input');
                //this.eInput.style.height = '100%';

                console.log('into init()', this.eInput);

                // https://jqueryui.com/datepicker/
                $(this.eInput).datepicker(
                {
                    dateFormat: 'dd/mm/yy',
                    changeMonth: true,
                    changeYear: true
                });
            };
        
            // gets called once when grid ready to insert the element
            Datepicker.prototype.getGui = function () 
            {
                console.log('into getGui', this.eInput);
                return this.eInput;
            };

            // focus and select can be done after the gui is attached
            Datepicker.prototype.afterGuiAttached = function ()
            {
                console.log('into afterGuiAttached');
                this.eInput.focus();
                this.eInput.select();
            };

            // returns the new value after editing
            Datepicker.prototype.getValue = function ()
            {
                console.log('into getValue');
                return this.eInput.value;
            };

            // any cleanup we need to be done here
            Datepicker.prototype.destroy = function()
            {
                // but this example is simple, no cleanup, we could
                // even leave this method out as it's optional
            };

            // if true, then this editor will appear in a popup
            Datepicker.prototype.isPopup = function()
            {
                // and we could leave this method out also, false is the default
                return false;
            };

            return Datepicker;
        }

        // 提前生成录入窗口,否则得不到modify_grid
        var win = new dhx.Window(
        {
            title: '操作窗口',
            footer: true,
            modal: true,
            width: 700,
            height: 500,
            closable: true,
            movable: true
        });

        win.footer.data.add(
        {
            type: 'button',
            id: '清空',
            value: '清空',
            view: 'flat',
            size: 'medium',
            color: 'primary',
        });

        win.footer.data.add(
        {
            type: 'button',
            id: '提交',
            value: '提交',
            view: 'flat',
            size: 'medium',
            color: 'primary',
        });

        var html = '<div id="modify_grid" class="ag-theme-alpine" style="width:100%;height:100%;"></div>';
        win.attachHTML(html);
        win.hide();
        var modify_grid_create = false;

        // 工具栏点击
        main_tb.events.on('click', function(id, e) 
        {
            switch (id)
            {
                case '刷新':
                    window.location.reload();
                    break;
                case '分页':
                    tb_paging_click(id);
                    break;
                case '修改':
                    tb_modify_click(id);
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

        function tb_paging_click(id)
        {
        }

        function tb_modify_click(id) 
        {
            var rows = data_grid_options.api.getSelectedRows();
            if (rows.length==0)
            {
                alert('请先选择要修改的记录');
                return;
            }
            console.log('select rows=', rows);

            win.show();
            if (modify_grid_create == false)
            {
                modify_grid_obj = JSON.parse('<?php echo $modify_value_json; ?>');

                new agGrid.Grid($$('modify_grid'), modify_grid_options);
                modify_grid_create = true;
            }
        }

        function tb_add_click(id) 
        {
            win.show();
            if (modify_grid_create == false)
            {
                new agGrid.Grid($$('modify_grid'), modify_grid_options);
                modify_grid_create = true;
            }
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
                        console.log('select date');
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

        win.footer.events.on('click', function (id)
        {
            if (id=='清空')
            {
                modify_grid_obj = JSON.parse('<?php echo $modify_value_json; ?>');
                modify_grid_options.api.setRowData(modify_grid_obj);
            }
            else if (id == '提交')
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
                        //console.log('modify_arr', modify_arr);
                    }
                });

                //console.log('modify_arr=', modify_arr);

                // 选择的记录
                var rows = data_grid_options.api.getSelectedRows();
                //console.log('rows=', rows);

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

                //console.log('modify_arr=', modify_arr);

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

                win.hide();
            }
        });
    </script>

</body>

</html>