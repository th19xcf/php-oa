<!-- v7.19.7.1.202412072045, from home -->
<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8'>
    <title>ag-grid_div</title>

    <script src='<?php base_url(); ?>/ag-grid/dist/ag-grid-locale-cn.js'></script>
    <script src='<?php base_url(); ?>/ag-grid/dist/ag-grid-community.noStyle.js'></script>
    <script src='<?php base_url(); ?>/assets/js/datepicker_brower.js'></script>
    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/ag-grid/dist/styles/ag-grid.css'>
    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/ag-grid/dist/styles/ag-theme-alpine.css'>

    <script src='<?php base_url(); ?>/dhtmlx/codebase/suite.js'></script>
    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/dhtmlx/codebase/suite.css'>

    <script src='<?php base_url(); ?>/echarts/echarts.js'></script>

    <style type='text/css'>
        div.box_1-1-1
        {
            width: 99%;
            height: 510px;
            margin: 4px;
            background-color: #f9f9f9;
            border: 1px solid #D0D0D0;
            box-sizing: border-box;
            float: left;
        }
        div.box_2-1-1
        {
            width: 99%;
            height: 255px;
            margin: 4px;
            background-color: #f9f9f9;
            border: 1px solid #D0D0D0;
            box-sizing: border-box;
            float: left;
        }
        div.box_2-1-2
        {
            width: 49%;
            height: 255px;
            margin: 4px;
            background-color: #f9f9f9;
            border: 1px solid #D0D0D0;
            box-sizing: border-box;
            float: left;
        }
        div.box_2-1-3
        {
            width: 31%;
            height: 255px;
            margin: 4px;
            background-color: #f9f9f9;
            border: 1px solid #D0D0D0;
            box-sizing: border-box;
            float: left;
        }
    </style>
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

        var chart_data_obj = JSON.parse('<?php echo $chart_data_json; ?>');
        var chart_drill_data_obj = [];

        if (chart_data_obj.length != 0)
        {
            div_block('chartbox');

            chart_now = '初始图形';
            chart_draw_init(chart_now, chart_data_obj);
        }
        else
        {
            div_block('databox');
        }

        $$('footbox').style.display = 'block';

        foot_data = '&nbsp&nbsp<b>' + <?php echo $func_id; ?> + ',条件:{}, 汇总:{}, 合计:{}, 平均:{}, 最大:{}, 最小:{}, 计数:{}</b>';
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

        function PopupInfo()
        {
            this.menu_1 = '';
            this.menu_2 = '';
            this.grid_api;
            this.node_id = '';
            this.col_name = ''; //调用方列名
            this.obj_name = ''; //对象名称
            this.full_name = ''; //对象全称
            this.max_rank = 0;
            this.obj = [];
        }

        function ColorInfo()
        {
            this.col_name_1 = '';
            this.option = '';
            this.col_name_2 = '';
            this.color_col_1 = '';
            this.color_col_2 = '';
            this.style = {'color':'red','font-weight':'bold'};
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

        var color_arr = new ColorInfo();
        var color_obj = JSON.parse('<?php echo $color_json; ?>');
        if (color_obj.length != 0)
        {
            color_arr = color_obj;
            color_arr['style'] = {'color':'red','font-weight':'bold'};
        }

        var chart = new Chart();

        // footbox显示
        var foot_data = '';
        var foot_upkeep = '';
        var foot_chart = '';

        var back_where = '<?php echo $back_where; ?>';
        var back_group = '<?php echo $back_group; ?>';
        var tip_column = '<?php echo $tip_column; ?>';

        foot_data = '&nbsp&nbsp<b>' + <?php echo $func_id; ?> + ',条件:{' + back_where.slice(0,40) + '}, 汇总:{' + back_group + '}, 合计:{}, 平均:{}, 最大:{}, 最小:{}</b>';
        $$('footbox').innerHTML = foot_data;

        // 字段数据
        var columns_obj = JSON.parse('<?php echo $columns_json; ?>');
        var columns_arr = Object.values(columns_obj);

        var column_name_arr = [], color_column_arr = [];
        for (var ii in columns_arr)
        {
            column_name_arr.push(columns_arr[ii]['列名']);
            if (columns_arr[ii]['可颜色标注'] == '1')
            {
                color_column_arr.push(columns_arr[ii]['列名']);
            }
        }

        // 工具栏数据
        var tb_obj = JSON.parse('<?php echo $toolbar_json; ?>');

        var table_modify_flag = tb_obj['整表授权'];  // 整表修改标志
        var table_modify_rows = [];
        var update_flag = '';  // modify或add

        // 条件选择
        var cond_object_value = JSON.parse('<?php echo $cond_obj_json; ?>');
        var update_object_value = JSON.parse('<?php echo $update_obj_json; ?>');

        // 调试数据
        var debug_sql = '<?php echo $SQL; ?>';
        debug_sql = debug_sql.replace(/~~/g, '"');

        // 生成主工具栏
        var data_tb = new dhx.Toolbar('data_tb', {css:'toobar-class'});
        data_tb.data.add({id:'刷新', type:'button', value:'刷新'});
        data_tb.data.add({id:'字段选择', type:'button', value:'字段选择'});
        data_tb.data.add({id:'设置条件', type:'button', value:'设置条件'});
        if (tb_obj['钻取授权'] == true)
        {
            data_tb.data.add({id:'数据钻取', type:'button', value:'数据钻取'});
        }
        if (color_column_arr.length > 0)
        {
            data_tb.data.add({id:'颜色标注', type:'button', value:'颜色标注'});
        }
        data_tb.data.add({id:'图形', type:'button', value:'图形'});
        data_tb.data.add({type:'separator'});
        if (tb_obj['修改授权'] == true)
        {
            data_tb.data.add({id:'单条修改', type:'button', value:'单条修改'});
            data_tb.data.add({id:'多条修改', type:'button', value:'多条修改'});
        }
        if (tb_obj['整表授权'] == true)
        {
            //data_tb.data.add({id:'整表修改', type:'button', value:'整表修改 - 关闭'});
            data_tb.data.add({id:'修改提交', type:'button', value:'修改提交'});
        }
        if (tb_obj['新增授权'] == true)
        {
            data_tb.data.add({id:'新增', type:'button', value:'新增'});
        }
        if (tb_obj['删除授权'] == true)
        {
            data_tb.data.add({id:'删除', type:'button', value:'删除'});
        }
        data_tb.data.add({type:'spacer'});
        if (tb_obj['SQL'] == true)
        {
            data_tb.data.add({id:'SQL', type:'button', value:'SQL'});
        }
        if (tb_obj['数据整理'] == true)
        {
            data_tb.data.add({id:'数据整理', type:'button', value:'数据整理'});
        }
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
        var chart_now = '初始图形';
        var chart_tb = new dhx.Toolbar('chart_tb', {css:'toobar-class'});
        chart_tb.data.add({id:'返回', type:'button', value:'返回'});
        chart_tb.data.add({id:'刷新', type:'button', value:'刷新'});
        chart_tb.data.add({id:'初始图形', type:'button', value:'初始图形'});
        chart_tb.data.add({id:'钻取图形', type:'button', value:'钻取图形'});
        chart_tb.data.add({id:'个性图形', type:'button', value:'个性图形'});
        chart_tb.data.add({type:'spacer'});
        if (tb_obj['SQL'] == true)
        {
            chart_tb.data.add({id:'SQL', type:'button', value:'SQL'});
        }

        // 生成data_grid
        var data_page = 500;
        var data_columns_obj = JSON.parse('<?php echo $data_col_json; ?>');

        var data_columns_arr = []; // 数据表使用
        data_columns_arr = Object.values(data_columns_obj);

        var data_grid_obj = JSON.parse('<?php echo $data_value_json; ?>');
        var data_last_selected = [];

        // 字符转换
        for (let ii in columns_obj)
        {
            if (columns_obj[ii].字符转换 != '1') continue;
            for (let jj in data_grid_obj)
            {
                data_grid_obj[jj][columns_obj[ii].字段名] = data_grid_obj[jj][columns_obj[ii].字段名].replace(/~~/g,'"');
            }
        }

        // 字段排序,设置cell格式
        for (let ii in data_columns_arr)
        {
            for (let jj in columns_obj)
            {
                if (columns_obj[jj].列名 != data_columns_arr[ii].field && data_columns_arr[ii].field != '序号') continue;

                if (columns_obj[jj].类型 == '数值' || data_columns_arr[ii].type == 'numericColumn')
                {
                    data_columns_arr[ii].comparator = value_sort;
                }
                if (columns_obj[jj].提示条件 != '' || columns_obj[jj].异常条件 != '' || columns_obj[jj].可颜色标注 == '1')                
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
                editable: (params) =>
                {
                    if (table_modify_flag == false) return true;  //readonly时,可以编辑

                    for (var ii in columns_obj)
                    {
                        if (columns_obj[ii].列名 != params.colDef.field) continue;
                        return (columns_obj[ii].可修改 == '1' || columns_obj[ii].可修改 == '2') ? true : false;
                    }

                    return false;
                },
            },
            readOnlyEdit: !table_modify_flag,
            rowData: data_grid_obj,
            rowSelection: 'multiple',
            pagination: true,
            paginationPageSize: 500,
            paginationPageSizeSelector: [500, 1000, 2000],
            localeText: AG_GRID_LOCALE_CN,

            //onSelectionChanged: onSelectionChanged,
            onGridReady: (params) => 
            {
                console.log('datagrid ready');

                let disp_col_obj = JSON.parse('<?php echo $disp_col_json; ?>')
                if (disp_col_obj.length == 0) return;

                let columns_arr = params.api.getColumns();

                let col_arr = [];
                for (let ii in columns_arr)
                {
                    for (let jj in disp_col_obj)
                    {
                        if (columns_arr[ii]['colId'] == disp_col_obj[jj])
                        {
                            col_arr.push(columns_arr[ii]['colId']);
                            break;
                        }
                    }
                }

                if ((disp_col_obj.length - col_arr.length) <= 2) // 基本相符
                {
                    let arr = [];
                    for (let ii in columns_arr)
                    {
                        arr.push(columns_arr[ii]['colId']);
                    }
                    params.api.setColumnsVisible(arr, false);

                    params.api.setColumnsVisible(col_arr, true);
                }
            },
            onCellEditRequest: (event) => 
            {
                if (table_modify_flag == false)
                {
                    alert('数据在此处修改无效,请点击`单条修改`或`多条修改`按钮进行修改');
                }
            },
            onCellValueChanged : (params) => 
            {
                if (params.newValue != params.oldValue)
                {
                    if (table_modify_rows.indexOf(params.rowIndex) == -1)
                    {
                        table_modify_rows.push(params.rowIndex);
                    }

                    params.colDef.cellStyle = (p) =>
                    {
                        //p.rowIndex.toString() === params.node.id ? {'background-color':'blue'} : {};
                        if (p.rowIndex.toString() === params.node.id)
                        {
                            return {'background-color':'yellow'};
                        }
                        return null;
                    }
                }

                params.api.refreshCells(
                {
                    force: true,
                    columns: [params.column.getId()],
                    rowNodes: [params.node]
                });
            },
        };

        var data_grid_api = agGrid.createGrid($$('data_grid'), data_grid_options);

        function onSelectionChanged()
        {
            //var row = data_grid_api.getSelectedRows();
            alert('selectionchanged');
        }

        function onCellValueChanged(params)
        {
            cond_object_value[params.data.列名] = params.newValue;
        }

        // 生成update_grid
        var update_grid_obj = JSON.parse('<?php echo $update_value_json; ?>');
        var object_obj = JSON.parse('<?php echo $object_json; ?>');

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
                {field:'取值', width:400, cellEditorSelector:cellEditorSelector}
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
                        return (columns_obj[ii].可新增 == '1' || columns_obj[ii].可修改 == '1' || columns_obj[ii].可修改 == '2') ? true : false;
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

        var update_grid_api = agGrid.createGrid($$('update_grid'), update_grid_options);

        // 生成cond_grid
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
                {
                    field:'汇总',
                    editable: (params) =>
                    {
                        // 根据配置判断是否可以修改
                        if (params.colDef.field != '汇总') return false;

                        var col_name = params.data.列名;

                        for (var ii in columns_obj)
                        {
                            if (columns_obj[ii].列名 != col_name) continue;
                            return (columns_obj[ii].可汇总 == '1') ? true : false;
                        }

                        return false;
                    },
                    cellEditorSelector:cellEditorSelector
                },
                {
                    field:'条件1',
                    cellEditor: 'agSelectCellEditor',
                    cellEditorParams: 
                    {
                        values: ['','大于','等于','等于空','小于','大于等于','小于等于','不等于','不等于空','包含','不包含'],
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
                        values: ['','大于','等于','等于空','小于','大于等于','小于等于','不等于','不等于空','包含','不包含'],
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

        cond_grid_api = agGrid.createGrid($$('cond_grid'), cond_grid_options);

        // 钻取模块参数
        var drill_arr = JSON.parse('<?php echo $drill_json; ?>');
        var drill_selected = '';
        var drill_from = '', drill_data = {};

        // 当前menu
        var menu_value = JSON.parse('<?php echo $menu_json; ?>');

        // 弹窗选择
        var popup_grid_options = {};
        var popup_grid_obj = JSON.parse('<?php echo $popup_grid_json; ?>');
        var popup_obj_obj = JSON.parse('<?php echo $popup_obj_json; ?>');
        var popup_grid_api = null;

        var popup_arr = new PopupInfo();
        popup_arr.menu_1 = menu_value['menu_1'];
        popup_arr.menu_2 = menu_value['menu_2'];

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
            id: '添加',
            value: '添加',
            view: 'flat',
            size: 'medium',
            color: 'primary',
        });

        win_popup_set.footer.data.add(
        {
            type: 'button',
            id: '替换',
            value: '替换',
            view: 'flat',
            size: 'medium',
            color: 'primary',
        });

        var html = '<div id="popup_set_grid" class="ag-theme-alpine" style="width:100%;height:100%;"></div>';
        win_popup_set.attachHTML(html);
        win_popup_set.hide();

        // 部门窗口事件
        win_popup_set.events.on('afterShow', function(position, events)
        {
        });

        win_popup_set.events.on('afterHide', function(position, events)
        {
        });

        win_popup_set.footer.events.on('click', function (id)
        {
            if (id == '清空')
            {
                popup_grid_obj = JSON.parse('<?php echo $popup_grid_json; ?>');
                popup_grid_api.setGridOption('rowData', popup_grid_obj[popup_arr['obj_name']]);
            }

            else if (id == '添加' || id == '替换')
            {
                popup_grid_api.stopEditing();

                // 获表中的数据
                let send_obj = {};
                send_obj['操作'] = id;
                send_obj['列名'] = popup_arr['col_name'];
                send_obj['对象名称'] = popup_arr['obj_name'];
                send_obj['最大级别'] = popup_arr.max_rank;
                send_obj['本级全称'] = '';

                popup_grid_api.forEachNode((rowNode, index) => 
                {
                    send_obj[rowNode.data['表项']] = rowNode.data['取值'];

                    if (rowNode.data['取值'] != '')
                    {
                        if (send_obj['本级全称'] != '')
                        {
                            send_obj['本级全称'] += '>>';
                        }
                        send_obj['本级全称'] += rowNode.data['取值'];
                    }
                });

                dhx.ajax.post('<?php base_url(); ?>/frame/verify_popup/<?php echo $func_id; ?>', send_obj).then(function (data)
                {
                    if (popup_arr.menu_1 != menu_value['menu_1'] || popup_arr.menu_2 != menu_value['menu_2'])
                    {
                        alert('页面已切换,请重新输入');
                        return;
                    }

                    win_popup_set.hide();

                    let api = popup_arr.grid_api;
                    api.stopEditing();
                    let row_node = api.getRowNode(popup_arr.node_id);

                    if (id == '添加')
                    {
                        if (row_node.data['取值'] == '' || row_node.data['取值'] == undefined)
                        {
                            row_node.setDataValue('取值', data);
                        }
                        else
                        {
                            row_node.setDataValue('取值', row_node.data['取值'] + ',' + data);
                        }
                    }
                    else if (id == '替换')
                    {
                        row_node.setDataValue('取值', data);
                    }
                }).catch(function (err)
                {
                    console.log(err);
                    alert('校验信息失败, ' + " " + err.statusText);
                });
            }
        });

        //////////////////////////
        // 颜色设置窗口
        // 弹窗选择
        var color_grid_options = {};
        var color_grid_obj = JSON.parse('<?php echo $popup_grid_json; ?>');
        var color_grid_api = null;

        var win_color_set = new dhx.Window(
        {
            title: '颜色标注输入窗口',
            footer: true,
            modal: true,
            width: 600,
            height: 500,
            closable: true,
            movable: true
        });

        win_color_set.footer.data.add(
        {
            type: 'button',
            id: '确定',
            value: '确定',
            view: 'flat',
            size: 'medium',
            color: 'primary',
        });

        var html = '<div id="color_set_grid" class="ag-theme-alpine" style="width:100%;height:100%;"></div>';
        win_color_set.attachHTML(html);
        win_color_set.hide();

        win_color_set.footer.events.on('click', function (id)
        {
            if (id != '确定') return;

            color_grid_api.stopEditing();

            // 获表中的数据
            color_grid_api.forEachNode((rowNode, index) => 
            {
                color_arr['color_col_1'] =  color_arr['col_name_1'];
                color_arr['color_col_2'] = color_arr['col_name_2'];
                color_arr['col_name_1'] = rowNode.data['字段一'];
                color_arr['option'] = rowNode.data['比较符'];
                color_arr['col_name_2'] = rowNode.data['字段二'];
            });

            win_color_set.hide();
        });

        //////////////////////////

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

        var chart_grid_api = null;
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
                        values: ['X轴（下方）','X轴（上方）','Y轴（左侧）','Y轴（右侧）'],
                    },
                },
                {
                    field: '图形类型',
                    width: 120,
                    cellEditor: 'agSelectCellEditor',
                    cellEditorParams:
                    {
                        values: ['饼图','折线图','柱状图', '散点图', '雷达图'],
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
                case '颜色标注':
                    if (color_grid_api != null)
                    {
                        color_grid_api.destroy();
                        color_grid_api = null;
                    }

                    color_grid_options = 
                    {
                        columnDefs:
                        [
                            {
                                field: '字段一',
                                width: 180,
                                cellEditor: 'agSelectCellEditor',
                                cellEditorParams: 
                                {
                                    values: color_column_arr,
                                },
                            },
                            {
                                field: '比较符',
                                width: 120,
                                cellEditor: 'agSelectCellEditor',
                                cellEditorParams: 
                                {
                                    values: ['等于','不等于','大于','大于等于','小于','小于等于'],
                                },
                            },
                            {
                                field: '字段二',
                                width: 180,
                                cellEditor: 'agSelectCellEditor',
                                cellEditorParams: 
                                {
                                    values: color_column_arr,
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
                        rowData:
                        [
                            {'字段一':'', '比较符':'', '字段二':''},
                        ]
                    };

                    win_color_set.show();
                    color_grid_api = agGrid.createGrid($$('color_set_grid'), color_grid_options);
                    break;
                case '图形':
                    div_block('chartbox');
                    break;
                case '单条修改':
                case '多条修改':
                    var rows = data_grid_api.getSelectedRows();

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

                        update_grid_api.setGridOption('rowData', rowData);
                    }

                    $$('footbox').innerHTML = '&nbsp&nbsp<b>' + <?php echo $func_id; ?> + ',提交记录:{' + foot_upkeep + '}</b>';
                    div_block('updatebox');
                    break;
                case '整表修改':
                    if (table_modify_flag == false)
                    {
                        table_modify_flag = true;
                        data_tb.data.update('整表修改',{value:'整表修改 - 打开'});
                    }
                    else
                    {
                        table_modify_flag = false;
                        data_tb.data.update('整表修改',{value:'整表修改 - 关闭'});
                    }
                    break;
                case '修改提交':
                    let send_arr = [];
                    data_grid_api.stopEditing();
                    data_grid_api.forEachNode((rowNode, index) => 
                    {
                        if (table_modify_rows.indexOf(index) != -1)
                        {
                            send_arr.push(rowNode.data);
                        }
                    });

                    var url = '<?php base_url(); ?>/frame/update_table/<?php echo $func_id; ?>';
                    dhx.ajax.post(url, send_arr).then(function (data)
                    {
                        alert(data);
                        window.location.reload();
                    }).catch(function (err)
                    {
                        alert('status' + " " + err.statusText);
                    });

                    break;
                case '新增':
                    if (update_flag != id)
                    {
                        update_flag = id;
                        // 清空
                        update_grid_obj = JSON.parse('<?php echo $add_value_json; ?>');
                        update_grid_api.setGridOption('rowData', update_grid_obj);
                    }

                    $$('footbox').innerHTML = '&nbsp&nbsp<b>' + <?php echo $func_id; ?> + ',提交记录:{' + foot_upkeep + '}</b>';
                    div_block('updatebox');
                    break;
                case '删除':
                    var rows = data_grid_api.getSelectedRows();
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
                    var rows = data_grid_api.getSelectedRows();
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

                    drill_selected = '';
                    drill_arr = JSON.parse('<?php echo $drill_json; ?>');

                    if (drill_arr.length == 0)
                    {
                        alert('没有设置钻取条件');
                        return;
                    }

                    var radio_arr = [];
                    for (let ii in drill_arr)
                    {
                        let radio = {};
                        radio['type'] = 'radioButton';
                        radio['text'] = drill_arr[ii]['页面选项'];
                        radio['value'] = drill_arr[ii]['页面选项'];
                        radio['id'] = drill_arr[ii]['页面选项'];
                        radio_arr.push(radio);
                    }

                    drill_from = 'data';
                    drill_select(radio_arr);
                    break;
                case '数据整理':
                    var url = '<?php base_url(); ?>/frame/upkeep/<?php echo $func_id; ?>';
                    dhx.ajax.post(url, null).then(function (data)
                    {
                        alert(data);
                        window.location.reload();
                    }).catch(function (err)
                    {
                        alert('status' + " " + err.statusText);
                    });
                    break;
                case 'SQL':
                    console.log('ID=[ ', '<?php echo $func_id; ?>', ' ]');
                    console.log('SQL=[ ', debug_sql, ' ]');
                    break;
                case '导入':
                    let import_func_id = '<?php echo $func_id; ?>' + '88';
                    parent.window.goto(import_func_id,'导入-'+'<?php echo $import_tag_name; ?>','upload/init/'+import_func_id+'/<?php echo $import_module; ?>');
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
                data_grid_api.setGridOption('paginationPageSize', Number(data_page));
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
                    update_grid_api.setGridOption('rowData', update_grid_obj);
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
                    cond_grid_api.setGridOption('rowData', cond_grid_obj);
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
                    window.location.reload();
                    break;
                case '初始图形':
                    chart_now = '初始图形';
                    chart_draw_init(chart_now, chart_data_obj);
                    break;
                case '个性图形':
                    chart_now = '个性图形';
                    tb_chart();
                    break;
                case '钻取图形':
                    if (chart_drill_data_obj.length == 0)
                    {
                        alert('没有钻取图形');
                        break;
                    }
                    chart_now = '钻取图形';
                    chart_draw_init(chart_now, chart_drill_data_obj);
                    break;
                case 'SQL':
                    let chart_data = [];
                    if (chart_now == '初始图形')
                    {
                        chart_data = chart_data_obj;
                    }
                    else if (chart_now == '钻取图形')
                    {
                        chart_data = chart_drill_data_obj;
                    }
                    for (let ii in chart_data)
                    {
                        for (let jj in chart_data[ii])
                        {
                            console.log('图形名称=[ ', chart_data[ii][jj]['图形名称'], ' ]');
                            console.log('SQL=[ ', chart_data[ii][jj]['SQL'].replace(/~~/g,'"'), ' ]');
                        }
                    }
                    break;
            }
        });

        // 选择字段是否显示
        function tb_select_field()
        {
            var checkbox_arr = [];
            var key = '<?php echo $primary_key; ?>';
            var columns_arr = data_grid_api.getColumns();

            let col = {};
            col['type'] = 'checkbox';
            col['text'] = '全选';
            col['id'] = '全选';
            col['checked'] = false;
            checkbox_arr.push(col);

            col = {};
            col['type'] = 'checkbox';
            col['text'] = '全不选';
            col['id'] = '全不选';
            col['checked'] = false;
            checkbox_arr.push(col);

            for (let ii in columns_arr)
            {
                if (columns_arr[ii]['colId'] == key) continue;

                let col = {};
                col['type'] = 'checkbox';
                col['text'] = columns_arr[ii]['colId'];
                col['id'] = columns_arr[ii]['colId'];
                col['checked'] = columns_arr[ii]['visible'];
                checkbox_arr.push(col);
            }

            const form_field = new dhx.Form(null, 
            {
                rows: checkbox_arr
            });

            form_field.getItem('全选').events.on('Change', function(value)
            {
                if (form_field.getItem('全选').isChecked())
                {
                    form_field.getItem('全不选').setValue(false);
                }

                let col_arr = [];
                for (let ii in columns_arr)
                {
                    if (columns_arr[ii]['colId'] == '<?php echo $primary_key; ?>') continue;
                    form_field.getItem(columns_arr[ii]['colId']).setValue(true);
                    col_arr.push(columns_arr[ii]['colId']);
                }
                data_grid_api.setColumnsVisible(col_arr, true);
            });

            form_field.getItem('全不选').events.on('Change', function(value)
            {
                if (form_field.getItem('全不选').isChecked())
                {
                    form_field.getItem('全选').setValue(false);
                }

                let col_arr = [];
                for (let ii in columns_arr)
                {
                    if (columns_arr[ii]['colId'] == '<?php echo $primary_key; ?>') continue;
                    form_field.getItem(columns_arr[ii]['colId']).setValue(false);
                    col_arr.push(columns_arr[ii]['colId']);
                }
                data_grid_api.setColumnsVisible(col_arr, false);
            });

            form_field.events.on('change', function(value)
            {
                if (value == '全选' || value == '全不选') return;

                let checked = form_field.getItem(value).getValue();
                let col_arr = [];
                col_arr.push(value);
                data_grid_api.setColumnsVisible(col_arr, checked);
            });

            const win_field = new dhx.Window(
            {
                title: '选择显示字段',
                footer: true,
                modal: true,
                width: 350,
                height: 400,
                closable: true,
                movable: true
            });

            win_field.attach(form_field);
            win_field.show();
        }

        // 钻取条件选择
        function drill_select(radio_arr)
        {
            if (radio_arr.length == 0)
            {
                alert('没有设置钻取条件');
                return;
            }

            const form_drill = new dhx.Form(null, 
            {
                rows:
                [
                    {
                        type: 'radioGroup',
                        options: 
                        {
                            rows: radio_arr
                        }
                    }
                ]
            });

            form_drill.events.on('change', function(value)
            {
                drill_selected = form_drill.getItem(value).getValue();
            });

            const win_drill = new dhx.Window(
            {
                title: '选择钻取条件',
                footer: true,
                modal: true,
                width: 350,
                height: 400,
                closable: true,
                movable: true
            });

            win_drill.footer.data.add(
            {
                type: 'button',
                id: '确定',
                value: '确定',
                view: 'flat',
                size: 'medium',
                color: 'primary',
            });

            win_drill.footer.events.on('click', function (id)
            {
                if (drill_selected == '')
                {
                    alert('请选择钻取条件');
                    return;
                }

                // 分发事件
                window.dispatchEvent(event_drill);

                win_drill.destructor();
            });

            win_drill.attach(form_drill);
            win_drill.show();
        }

        function tb_chart()
        {
            win_chart_set.show();
            if (chart_grid_new == false)
            {
                chart_grid_api = agGrid.createGrid($$('chart_set_grid'), chart_grid_options);
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

            cond_grid_api.stopEditing();
            cond_grid_api.forEachNode((rowNode, index) => 
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
                if (rowNode.data['条件1']!='' && rowNode.data['条件1']!='等于空' && rowNode.data['条件1']!='不等于空' && rowNode.data['参数1']=='')
                {
                    alert("'" + rowNode.data['字段名'] + "'" + '参数1,错误');
                    return;
                }
                if (rowNode.data['条件2']!=''  && rowNode.data['条件2']!='等于空'&& rowNode.data['条件2']!='不等于空' && rowNode.data['参数2']=='')
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
                let val = JSON.parse(data);
                debug_sql = val['SQL'];
                debug_sql = debug_sql.replace(/~~/g,'"');

                data_grid_obj = val['results'];
                data_grid_api.setGridOption('rowData', data_grid_obj);

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

                foot_data = '&nbsp&nbsp<b>' + <?php echo $func_id; ?> + ',条件:{' + disp_where.slice(0,40) + '} , 汇总:{' + disp_group + '} , 合计:{' + sum_str + '}, 平均:{' + average_str + '}, 最大:{' + max_str + '}, 最小:{' + min_str + '}, 计数:{' + count_str + '}</b>';
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

            cond_grid_api.stopEditing();
            cond_grid_api.forEachNode((rowNode, index) => 
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
                data_grid_api.setGridOption('rowData', data_grid_obj);

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

            update_grid_api.stopEditing();

            // 获得要提交的数据
            if (update_flag == '多条修改')  // 多条
            {
                update_grid_api.forEachNode((rowNode, index) => 
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
            }

            else  // 单条&新增
            {
                update_grid_api.forEachNode((rowNode, index) => 
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
            for (let ii in send_arr)
            {
                for (let jj in columns_obj)
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
                    if (data.indexOf('成功') != -1)
                    {
                        window.location.reload();
                    }
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
                $$('footbox').innerHTML = '&nbsp&nbsp<b>' + <?php echo $func_id; ?> + ',提交记录:{' + foot_upkeep + '}</b>';

                let key_arr = [];
                let key = '<?php echo $primary_key; ?>';

                // 选择要提交的记录
                let selected_rows = data_grid_api.getSelectedRows();
                let selected_key_arr = selected_rows.map(a => a[key]);

                key_arr = selected_key_arr;

                // 设置过滤条件记录
                var filter_rows = [];
                if (update_flag == '多条修改' && data_grid_api.isAnyFilterPresent() == true)
                {
                    data_grid_api.forEachNodeAfterFilter((rowNode, index) => 
                    {
                        filter_rows.push(rowNode.data);
                    });

                    // 交集
                    let filter_key_arr = filter_rows.map(a => a[key]);
                    key_arr = filter_key_arr.filter(a => selected_key_arr.includes(a));
                }

                var col = new ColumnInfo();
                col.col_name = key;
                col.fld_name = key;
                col.type = '主键';
                col.value = key_arr.join(',');

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
                    if (data.indexOf('成功') != -1)
                    {
                        window.location.reload();
                    }
                }).catch(function (err)
                {
                    alert('status' + " " + err.statusText);
                });
            }
        }

        function delete_submit(id)
        {
            let key_arr = [];
            let key = '<?php echo $primary_key; ?>';

            // 选择的记录
            let selected_rows = data_grid_api.getSelectedRows();
            let selected_key_arr = selected_rows.map(a => a[key]);

            key_arr = selected_key_arr;

            // 设置过滤条件记录
            let filter_rows = [];
            if (data_grid_api.isAnyFilterPresent() == true)
            {
                data_grid_api.forEachNodeAfterFilter((rowNode, index) => 
                {
                    filter_rows.push(rowNode.data);
                });

                // 交集
                let filter_key_arr = filter_rows.map(a => a[key]);
                key_arr = filter_key_arr.filter(a => selected_key_arr.includes(a));
            }

            let col = new ColumnInfo();
            col.col_name = key;
            col.fld_name = key;
            col.type = '主键';
            col.value = key_arr.join(',');

            if (confirm('是否删除记录:'+'`'+key+'`={'+col.value+'}') == false)
            {
                return;
            }

            let send_arr = [];
            send_arr.push(col);

            let url = '<?php base_url(); ?>/frame/delete_row/<?php echo $func_id; ?>';
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
                        if (val_1 == val_2) error = true;
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

            str = str.replace(/；/g,';');

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

                if (columns_obj[jj].可颜色标注 == '1')
                {
                    if (color_arr['color_col_1'] == columns_obj[jj].列名 && color_arr['col_name_1'] != columns_obj[jj].列名)
                    {
                        return {'color':'black','font-weight':'normal'};
                    }
                    if (color_arr['color_col_2'] == columns_obj[jj].列名 && color_arr['col_name_2'] == columns_obj[jj].列名)
                    {
                        return {'color':'black','font-weight':'normal'};
                    }

                    if (color_arr['col_name_1'] != columns_obj[jj].列名 && color_arr['col_name_2'] != columns_obj[jj].列名) continue;

                    let rc = false;
                    val_1 = Number(params.data[color_arr['col_name_1']]);
                    val_2 = Number(params.data[color_arr['col_name_2']]);

                    switch (color_arr['option'])
                    {
                        case '大于':
                            if (val_1 > val_2) rc = true;
                            break;
                        case '小于':
                            if (val_1 < val_2) rc = true;
                            break;
                        case '等于':
                            if (val_1 == val_2) rc = true;
                            break;
                        case '大于等于':
                            if (val_1 >= val_2) rc = true;
                            break;
                        case '小于等于':
                            if (val_1 <= val_2) rc = true;
                            break;
                        case '不等于':
                            if (val_1 != val_2) rc = true;
                            break;
                    }

                    if (rc == true)
                    {
                        return color_arr['style'];
                    }
                }

                if (columns_obj[jj].提示样式 == '' && columns_obj[jj].异常样式 == '' && columns_obj[jj].可颜色标注 != '1')
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

                if (params.colDef.field == '汇总')
                {
                    if (columns_obj[ii].可汇总 == '1')
                    {
                        return {
                            component: 'agSelectCellEditor',
                            params: {
                                values: ['','√'],
                                },
                        };
                    }
                    return null;
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
                    case '弹窗':
                        if (popup_grid_api != null)
                        {
                            popup_grid_api.destroy();
                            popup_grid_api = null;
                        }

                        popup_grid_options = {};
                        popup_grid_obj = JSON.parse('<?php echo $popup_grid_json; ?>');
                        popup_arr = new PopupInfo();
                        popup_arr.menu_1 = menu_value['menu_1'];
                        popup_arr.menu_2 = menu_value['menu_2'];

                        popup_arr.obj_name = columns_obj[ii].对象名称;
                        popup_arr.grid_api = params.api;
                        popup_arr.node_id = params.node.id;
                        popup_arr.col_name = col_name;
                        let popup_obj = popup_obj_obj[columns_obj[ii].对象名称];
                        for (let item in popup_obj)
                        {
                            popup_arr.obj[item] = popup_obj[item]['本级初始值'];
                        }

                        popup_grid_options = 
                        {
                            columnDefs:
                            [
                                {field:'表项', editable:false},
                                {field:'级别', editable:false},
                                {
                                    field: '取值',
                                    width: 250,
                                    editable: (params) =>
                                    {
                                        if (params.data.取值 != '' && params.data.取值 == popup_obj[params.data.表项]['本级初始值']) 
                                        {
                                            return false;
                                        }
                                        return true;
                                    },
                                    cellEditorSelector: (params) => 
                                    {
                                        let data_arr = [];
                                        data_arr[0] = '';

                                        let item = popup_obj_obj[columns_obj[ii].对象名称][params.data.表项];  // 如:四级部门

                                        if (JSON.stringify(item) != '{}')
                                        {
                                            if (params.data.级别 == '1')
                                            {
                                                for (let val in item[''])
                                                {
                                                    data_arr.push(item[''][val]);
                                                }
                                            }
                                            else
                                            {
                                                let parent_value = popup_arr.obj[item['上级级别名称']]; // 如:`三级部门`的值

                                                for (let val in item[parent_value])
                                                {
                                                    data_arr.push(item[parent_value][val]);
                                                }
                                            }
                                        }

                                        return {
                                            component: 'agSelectCellEditor',
                                            params: {
                                                values: data_arr
                                            },
                                        };
                                    },
                                    onCellValueChanged : (params) => 
                                    {
                                        popup_arr.obj[params.data.表项] = params.newValue;
                                        if (params.data.取值 != '')
                                        {
                                            popup_arr.max_rank = params.data.级别;
                                        }

                                        // 清空下级部门
                                        popup_grid_api.forEachNode((rowNode, index) => 
                                        {
                                            if (rowNode.data['级别'] > popup_arr.max_rank)
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
                            rowData: popup_grid_obj[columns_obj[ii].对象名称]
                        };

                        win_popup_set.show();
                        popup_grid_api = agGrid.createGrid($$('popup_set_grid'), popup_grid_options);
                        break;
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
                chart_grid_api.setGridOption('rowData', chart_grid_obj);
            }
            else if (id == '删除')
            {
                var pos = -1;
                chart_grid_api.forEachNode((rowNode, index) => 
                {
                    pos = pos + 1;
                    if (rowNode.isSelected() == true)
                    {
                        chart_grid_obj.splice(pos, 1);
                        chart_grid_api.setGridOption('rowData', chart_grid_obj);
                    }
                });
            }
            else if (id == '确定')
            {
                chart_draw_self();
            }
        });


        function chart_draw_self()
        {
            chart_now = '个性图形';
            win_chart_set.hide();

            // 删除图形容器
            let chart_div = $$('chart_draw');
            let child_div = chart_div.childNodes;

            for (let ii = child_div.length-1; ii >= 0; ii --)
            {
                chart_div.removeChild(child_div[child_div.length-1]);
            }

            // 生成图形容器
            let sub_div = document.createElement('div');
            sub_div.className = 'box_1-1-1';
            chart_div.appendChild(sub_div);

            let chart_data = [];
            let num = 0;
            chart_grid_api.forEachNode((rowNode, index) => 
            {
                let data = [];
                if (num == 0)
                {
                    chart_data['图形名称'] = '个性图形';
                    chart_data['图形类型'] = rowNode.data['图形类型'];
                    chart_data['字段'] = [];
                    chart_data['数据'] = [];
                }

                chart_data['字段'][rowNode.data['字段名称']] = [];
                chart_data['字段'][rowNode.data['字段名称']]['列名'] = rowNode.data['字段名称'];
                chart_data['字段'][rowNode.data['字段名称']]['字段名称'] = rowNode.data['字段名称'];
                chart_data['字段'][rowNode.data['字段名称']]['坐标轴'] = rowNode.data['坐标轴'];
                chart_data['字段'][rowNode.data['字段名称']]['图形类型'] = rowNode.data['图形类型'];
                num ++;
            });

            let rows = data_grid_api.getSelectedRows();

            for (let ii in rows)
            {
                let row_data = {};
                for (let jj in chart_data['字段'])
                {
                    let fld_name = chart_data['字段'][jj]['字段名称'];
                    row_data[fld_name] = rows[ii][fld_name];
                }
                chart_data['数据'].push(row_data);
            }

            let option = chart_option(chart_data);
            let chart_win = echarts.init(sub_div);
            chart_win.setOption(option);
        }

        function chart_draw_init(chart_now, chart_data)
        {
            let chart_div = $$('chart_draw');
            let child_div = chart_div.childNodes;

            // 删除图形容器
            for (let ii = child_div.length-1; ii >= 0; ii --)
            {
                chart_div.removeChild(child_div[child_div.length-1]);
            }

            var sub_div = [], sub_div_num = -1;
            for (let ii in chart_data)
            {
                for (let jj in chart_data[ii])
                {
                    // 生成图形容器
                    sub_div_num = sub_div_num + 1;

                    sub_div[sub_div_num] = document.createElement('div');
                    sub_div[sub_div_num].className = 'box_' + chart_data[ii][jj]['页面布局'];
                    chart_div.appendChild(sub_div[sub_div_num]);

                    let option = chart_option(chart_data[ii][jj]);

                    let chart_win = echarts.init(sub_div[sub_div_num]);
                    chart_win.setOption(option);
                    chart_win.on('click', chart_drill);
                }
            }
        }

        function chart_option(chart_data)
        {
            let option = {};
            switch (chart_data['图形类型'])
            {
                case '饼图':
                    option = 
                    {
                        title:
                        {
                            show: true,
                            text: chart_data['图形名称'],
                        },
                        legend:
                        {
                            bottom: 2,
                        },
                        tooltip:
                        {
                            //trigger: 'item',
                        },
                        dataset:
                        {
                            source: chart_data['数据']
                        },
                        series:
                        [{
                            //radius: ['40%','70%'],
                            type: 'pie'
                        }]
                    }
                    break;
                case '柱状图':
                case '折线图':
                    let dem = [], x_axis = [], y_axis = [];
                    let x_bottom = false, x_top = false, y_left = false, y_right = false;
                    for (let kk in chart_data['字段'])
                    {
                        if (chart_data['字段'][kk]['坐标轴'] == 'X轴（下方）' && x_bottom == false)
                        {
                            x_axis.push({position:'bottom'});
                            x_bottom = true;
                        }
                        else if (chart_data['字段'][kk]['坐标轴'] == 'X轴（下方）' && x_top == false)
                        {
                            x_axis.push({position:'top'});
                            x_top = true;
                        }
                        else if (chart_data['字段'][kk]['坐标轴'] == 'Y轴（左侧）' && y_left == false)
                        {
                            y_axis.push({position:'left'});
                            y_left = true;
                        }
                        else if (chart_data['字段'][kk]['坐标轴'] == 'Y轴（右侧）' && y_right == false)
                        {
                            y_axis.push({position:'right'});
                            y_right = true;
                        }
                        if (chart_data['字段'][kk]['图形类型'] == '柱状图')
                        {
                            if (chart_data['字段'][kk]['坐标轴'] == 'Y轴（左侧）')
                            {
                                dem.push({type:'bar', yAxisIndex:0});
                            }
                            else if (chart_data['字段'][kk]['坐标轴'] == 'Y轴（右侧）')
                            {
                                dem.push({type:'bar', yAxisIndex:1});
                            }
                        }
                        else if (chart_data['字段'][kk]['图形类型'] == '折线图')
                        {
                            if (chart_data['字段'][kk]['坐标轴'] == 'Y轴（左侧）')
                            {
                                dem.push({type:'line', smooth:true, yAxisIndex:0});
                            }
                            else if (chart_data['字段'][kk]['坐标轴'] == 'Y轴（右侧）')
                            {
                                dem.push({type:'line', smooth:true, yAxisIndex:1});
                            }
                        }
                    }

                    option = 
                    {
                        title:
                        {
                            show: true,
                            id: chart_data['图形编号'],
                            text: chart_data['图形名称'],
                            triggerEvent: true,
                        },
                        legend:
                        {
                            bottom: 2,
                        },
                        tooltip:
                        {
                            trigger: 'axis',
                            axisPointer: { type:'cross' }
                        },
                        toolbox:
                        {
                            feature:
                            {
                                dataview: { show: true },
                                magicType: { show: true, type: ['line', 'bar', 'stack'] },
                                restore: {show: true},
                                saveAsImage: {show: true}
                            }
                        },
                        dataset:
                        {
                            source: chart_data['数据']
                        },
                        xAxis: { type: 'category' },
                        yAxis: y_axis,
                        series: dem
                    }
                    break;
                case '散点图':
                    option = 
                    {
                        title:
                        {
                            show: true,
                            text: chart_data['图形名称'],
                        },
                        //legend: {},
                        tooltip:
                        {
                            show: true,
                            trigger: 'item',
                            formatter: '{a},{b},{c}'
                        },
                        toolbox:
                        {
                        },
                        dataset:
                        {
                            source: chart_data['数据']
                        },
                        xAxis: {},
                        yAxis: {},
                        series: 
                        {
                            type:'scatter',
                            /*
                            label:
                            {
                                show: true,
                                formatter: function (params)
                                {
                                    return params.value[1];
                                }
                            }
                            */
                        }
                    }
                    break;
            }

            return option;
        }


        function chart_drill(params)
        {
            let chart_data = [];
            if (chart_now == '个性图形') return;
            else if (chart_now == '初始图形') chart_data = chart_data_obj;
            else if (chart_now == '钻取图形') chart_data = chart_drill_data_obj;

            let value = params.value;
            let arr = value['SID'].split('^');
            let drill_arr = chart_data[arr[0]][arr[1]]['钻取模块'];

            var radio_arr = [];
            for (let ii in chart_data[arr[0]][arr[1]]['钻取模块'])
            {
                let radio = {};
                radio['type'] = 'radioButton';
                radio['text'] = drill_arr[ii]['钻取选项'];
                radio['value'] = drill_arr[ii]['钻取选项']+'^'+drill_arr[ii]['图形模块']+'^'+drill_arr[ii]['模块名称'];
                radio['id'] = drill_arr[ii]['钻取选项'];
                radio_arr.push(radio);
            }

            drill_from = 'chart';
            drill_data = params['value'];
            drill_select(radio_arr);
            return;
        }

        // 创建事件
        const event_drill = new CustomEvent('event_drill');

        // 监听事件
        window.addEventListener('event_drill', (e) => 
        {
            if (drill_selected == '') return;

            if (drill_from == 'data')
            {
                let drill_item = '';
                for (let ii in drill_arr)
                {
                    if (drill_arr[ii]['页面选项'] != drill_selected) continue;
                    drill_item = drill_arr[ii];
                    break;
                }

                drill_item['钻取字段'] = drill_item['钻取字段'].replace(/；/g,';');
                let nl_arr = drill_item['钻取字段'].split(';');
                let send_obj = {};
                let ajax = false;

                let rows = data_grid_api.getSelectedRows();
                for (let ii in nl_arr)
                {
                    if (rows[0][nl_arr[ii]] == '') continue;
                    send_obj[nl_arr[ii]] = rows[0][nl_arr[ii]];
                    ajax = true;
                }

                if (ajax == false)
                {
                    alert('钻取字段为空,无法钻取');
                    return;
                }

                send_obj['钻取字段'] = drill_item['钻取字段'];
                send_obj['钻取条件'] = drill_item['钻取条件'];
                send_obj['颜色标注'] = color_arr;
                send_obj['字段选择'] = [];

                let key = '<?php echo $primary_key; ?>';
                let columns_arr = data_grid_api.getColumns();
                //let columns_arr = data_grid_api.getAllDisplayedColumns();
                let hide_num = 0;
                for (let ii in columns_arr)
                {
                    if (columns_arr[ii]['colId'] == key) continue;

                    if (columns_arr[ii]['visible'] == false)
                    {
                        hide_num = hide_num + 1;
                    }
                    else
                    {
                        send_obj['字段选择'].push(columns_arr[ii]['colId']);
                    }
                }
                if (hide_num == 0) //未做字段选择
                {
                    send_obj['字段选择'] = [];
                }

                send_str = JSON.stringify(send_obj);
                parent.window.goto(drill_item['功能编码'],'钻取-'+drill_item['标签名称'],'frame/init/'+drill_item['功能编码']+'/'+'<?php echo $func_id; ?>'+'/'+send_str);
            }
            else if (drill_from == 'chart')
            {
                let send_arr = [];
                if(chart_now == '初始图形') send_arr.push({'钻取级别':0});
                else if(chart_now == '钻取图形') send_arr.push({'钻取级别':1});
                send_arr.push({'钻取选项':drill_selected});
                send_arr.push(drill_data);

                let url = '<?php base_url(); ?>/frame/chart_drill/<?php echo $func_id; ?>';
                dhx.ajax.post(url, send_arr).then(function (data)
                {
                    chart_drill_data_obj = JSON.parse(data);

                    chart_now = '钻取图形';
                    chart_draw_init(chart_now, chart_drill_data_obj);
                }).catch(function (err)
                {
                    alert('status' + " " + err.statusText);
                });
            }
        });

    </script>

</body>

</html>
