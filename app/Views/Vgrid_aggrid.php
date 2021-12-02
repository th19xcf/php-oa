<!-- v2.0.0.1.202112021020, from office -->
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
    <div id='popup' style='width:500px; height:600px; background-color:lightblue;'>
        <div id='cond_toolbar'></div>
        <div id='cond_grid' style='width:100%; height:92%; background-color:lightblue;'></div>
    </div>


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
        main_tb.data.add({id:'名称', type:'title', value: '主菜单-->'});
        main_tb.data.add(
        {
            id: '刷新',
            type: 'button',
            value: '刷新'
        });
        main_tb.data.add({
            type: 'separator'
        });
        main_tb.data.add({
            id: '新增',
            type: 'button',
            value: '新增'
        });

        var data_columns_obj = JSON.parse('<?php echo $col_json; ?>');
        //console.log('data_column_obj', data_columns_obj);

        var data_columns_arr = []; // 数据表使用
        data_columns_arr = Object.values(data_columns_obj);
        //console.log('data_column_arr', data_columns_arr);

        var data_grid_obj = JSON.parse('<?php echo $data_json; ?>');
        //console.log('data_grid_obj', data_grid_obj);

        // let the grid know which columns and what data to use
        const data_grid_columns = {
            columnDefs: data_columns_arr,
            rowData: data_grid_obj,
            rowSelection: 'multiple',
            pagination: true
        };

        // lookup the container we want the Grid to use
        //const eGridDiv = document.querySelector('#gridbox');

        // create the grid passing in the div to use together with the columns & data we want to use
        new agGrid.Grid($$('gridbox'), data_grid_columns);

        // 提前生成录入窗口,否则得不到add_grid
        var win = new dhx.Window(
        {
            title: '录入窗口',
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
            id: '提交',
            value: '提交',
            view: 'flat',
            size: 'medium',
            color: 'primary',
        });

        var html = '<div id="add_grid" style="width:200px;height:300px;background-color:lightblue;">add grid 1</div>';
        win.attachHTML(html);
        win.hide();

        // 工具栏点击
        main_tb.events.on('click', function(id, e) 
        {
            switch (id) {
                case '新增':
                    tb_add_click();
                    break;
            }
        });

        function tb_add_click() {
            /*
            var add_num = 0;
            var html = '<div id="add_grid" style="width:200px;height:300px;background-color:lightblue;">add grid 1</div>';

            var win = new dhx.Window(
            {
                title: '录入窗口',
                footer: true,
                modal: true,
                //html: html,
                width: 700,
                height: 500,
                closable: true,
                movable: true
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

            win.events.on("beforeShow", function(position)
            {
                console.log('before,',$$('add_grid'));
            });

            win.events.on("afterShow", function(position)
            {
                win.attachHTML(html);
                //alert('after,'+$$('add_grid'));
                console.log('after',$$('add_grid'));
                /*
                const columnDefs = [
                { field: "make" },
                { field: "model" },
                { field: "price" }
                ];

                // specify the data
                const rowData = [
                { make: "Toyota", model: "Celica", price: 35000 },
                { make: "Ford", model: "Mondeo", price: 32000 },
                { make: "Porsche", model: "Boxter", price: 72000 }
                ];

                // let the grid know which columns and what data to use
                const gridOptions = {
                columnDefs: columnDefs,
                rowData: rowData
                };

                const add_grid_columns = 
                {
                    columnDefs: [{field:'测试'}]
                };
                new agGrid.Grid($$('add_grid'), gridOptions);
            });

            win.footer.events.on("click", function (id) 
            {
                win.attachHTML(html);
                console.log('click',$$('add_grid'));
            });
            */
            win.show();
            console.log('add', $$('add_grid'));
            //win.attachHTML(html);
        }
    </script>

</body>

</html>