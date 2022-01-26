<!-- v1.1.0.0.202112021650, from office -->
<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8'>
    <title>ag-grid</title>

    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/ag-grid/dist/styles/ag-grid.css'>
    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/ag-grid/dist/styles/ag-theme-alpine.css'>
    <script src='<?php base_url(); ?>/ag-grid/dist/ag-grid-community.noStyle.js'></script>

    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/dhtmlx/codebase/suite.css'>
    <script src='<?php base_url(); ?>/dhtmlx/codebase/suite.js'></script>
</head>

<body>
    <div id='toolbarbox'></div>
    <div id='gridbox' class='ag-theme-alpine' style='width:100%; height:600px; background-color:lightblue;'></div>
    <div id='footbox' style='width:100%; height:10px; margin-top:5px; background-color: lightblue;'></div>

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
        main_tb.data.add({id:'名称', type:'title', value:'主菜单-->'});
        main_tb.data.add({id:'刷新', type:'button', value:'刷新'});
        main_tb.data.add({type: 'separator'});
        main_tb.data.add({id:'修改', type:'button', value: '修改'});
        main_tb.data.add({id:'新增', type:'button', value: '新增'});

        const layout = new dhx.Layout('gridbox', 
        {
            //type: "space",
            cols: 
            [
                {
                    id:'data_grid', 
                    header:'数据窗口', 
                    html:'<div id="data_grid" style="height:100%;"></div>',
                    resizable:true
                },
                {
                    id:'modify_grid', 
                    header:'录入窗口', 
                    width: '400px',
                    html:'<div id="modify_grid" style="height:100%;"></div>', 
                    resizable:true,
                    //collapsable:true,
                    hidden:true
                }
            ]
        });

        //console.log('data_grid=', $$('data_grid'));
        //console.log('modify_grid=', $$('modify_grid'));

        var data_columns_obj = JSON.parse('<?php echo $data_col_json; ?>');
        //console.log('data_column_obj', data_columns_obj);

        var data_columns_arr = []; // 数据表使用
        data_columns_arr = Object.values(data_columns_obj);
        //console.log('data_column_arr', data_columns_arr);

        var data_grid_obj = JSON.parse('<?php echo $data_value_json; ?>');
        //console.log('data_grid_obj', data_grid_obj);

        // let the grid know which columns and what data to use
        const data_grid_columns = 
        {
            columnDefs: data_columns_arr,
            rowData: data_grid_obj,
            rowSelection: 'multiple',
            pagination: true
        };

        // create the grid passing in the div to use together with the columns & data we want to use
        new agGrid.Grid($$('data_grid'), data_grid_columns);

        // 修改及新增记录使用
        var modify_columns_obj = JSON.parse('<?php echo $modify_col_json; ?>');
        var modify_columns_arr = Object.values(modify_columns_obj);
        var modify_grid_obj = JSON.parse('<?php echo $modify_value_json; ?>');

        //console.log('modify_columns_arr', modify_columns_arr);

        /*
        const modify_grid_columns = 
        {
            columnDefs: modify_columns_arr,
            rowData: modify_grid_obj
        };

        new agGrid.Grid($$('modify_grid'), modify_grid_columns);
        */

        const modify_grid_columns = 
        {
            columnDefs: 
            [
                {field:'字段名称', width:'120px', resizable:true},
                {field:'字段类型', width:'100px', resizable: true},
                {field:'字段值', width:'200px', resizable:true, editable:true, cellEditorSelector:cellEditorSelector}
            ],
            rowData: modify_grid_obj
        };
        //new agGrid.Grid($$('modify_grid'), modify_grid_columns);
        //layout.getCell('modify_grid').hide();

        // 工具栏点击
        main_tb.events.on('click', function(id, e) 
        {
            switch (id)
            {
                case '新增':
                    tb_add_click();
                    break;
            }
        });

        function tb_add_click()
        {
            console.log('click before, show=', layout.getCell('modify_grid').isVisible(), 'click=', $$('modify_grid'));
            if (layout.getCell('modify_grid').isVisible())
            {
                layout.getCell('modify_grid').hide();
                console.log('1=', $$('modify_grid'));
            }
            else
            {
                layout.getCell('modify_grid').show();
                layout.getCell('modify_grid').attachHTML('<div id="modify_grid" style="height:100%;">show</div>');
                console.log('2=', $$('modify_grid'));
            }
            console.log('click after, show=', layout.getCell('modify_grid').isVisible(), 'click=', $$('modify_grid'));
            //layout.getCell('modify_grid').show();
            //layout.getCell('modify_grid').toggle();
        }

        layout.events.on('afterResizeEnd', function(id)
        {
            layout.getCell('modify_grid').attachHTML('<div id="modify_grid" style="height:100%;">afterResizeEnd</div>');
            console.log('afterResizeEnd=', $$('modify_grid'));
            //new agGrid.Grid($$('modify_grid'), modify_grid_columns);
        });

        layout.events.on('afterShow', function(id)
        {
            layout.getCell('modify_grid').attachHTML('<div id="modify_grid" style="height:100%;">afterShow</div>');
            console.log('afterShow=', $$('modify_grid'));
            //new agGrid.Grid($$('modify_grid'), modify_grid_columns);
            var widget = layout.getCell('modify_grid').getWidget();
            console.log('afterShow, widget=', widget, 'grid=', $$('modify_grid'));

        });

        layout.events.on('afterHide', function(id)
        {
            //alert(document.querySelector('#modify_grid'));
            layout.getCell('modify_grid').attachHTML('<div id="modify_grid" style="height:100%;">afterHide</div>');
            console.log('afterHide=', $$('modify_grid'));
            //new agGrid.Grid($$('modify_grid'), modify_grid_columns);
        });

        function cellEditorSelector(params)
        {
            console.log('params', params.data.字段名称);
            if (params.data.字段名称 === '姓名')
            {
                return {
                component: 'numericCellEditor',
                };
            }

            if (params.data.字段名称 === '学校')
            {
                return {
                    component: 'agSelectCellEditor',
                    params: {
                        values: ['Male', 'Female']
                    },
                };
            }
        }

      //console.log(data_grid_columns.api.getColumnDefs());

    </script>

</body>

</html>