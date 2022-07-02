<!-- v1.2.1.1.202207022020, from home -->
<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8'>
    <title>部门</title>

    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/dhtmlx/codebase/suite.css'>
    <script src='<?php base_url(); ?>/dhtmlx/codebase/suite.js'></script>

    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/ag-grid/dist/styles/ag-grid.css'>
    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/ag-grid/dist/styles/ag-theme-alpine.css'>
    <script src='<?php base_url(); ?>/ag-grid/dist/ag-grid-locale-cn.js'></script>
    <script src='<?php base_url(); ?>/ag-grid/dist/ag-grid-community.noStyle.js'></script>

    <style type='text/css'>
        div.float_box
        {
            width: 46%;
            height: 640px;
            margin: 10px;
            background-color: #f9f9f9;
            border: 1px solid #D0D0D0;
            box-sizing: border-box;
            float: left;
        }
    </style>
</head>

<body>
    <div id='main_tb' ></div>
    <div class='float_box'>
        <div id='tree_box' style='height:95%;'></div>
    </div>

    <div class='float_box'>
        <div id='grid_box' class='ag-theme-alpine' style='width:100%; height:91.5%; background-color:lightblue;'></div>
    </div>

    <script type='text/javascript' charset='utf-8'>
        function $$(id)
        {
            return document.getElementById(id);
        }

        // 变量
        var selected_id = '';

        // tree视图
        var main_tb = new dhx.Toolbar('main_tb', {css:'toobar-class'});
        main_tb.data.add({id:'刷新', type:'button', value:'刷新'});
        main_tb.data.add({type:'separator'});
        main_tb.data.add({id:'修改部门名称', type:'button', value:'修改部门名称'});
        main_tb.data.add({id:'新增下级部门', type:'button', value:'新增下级部门'});
        main_tb.data.add({id:'删除部门', type:'button', value:'删除部门'});
        main_tb.data.add({type:'separator'});
        main_tb.data.add({id:'提交', type:'button', value:'提交'});

        var dept_obj = JSON.parse('<?php echo $dept_json; ?>');
        //var dept_tree = new dhx.Tree('tree_box', {checkbox: true});
        var dept_tree = new dhx.Tree('tree_box');
        dept_tree.data.parse(dept_obj);

        //grid视图
        const grid_options = 
        {
            columnDefs: 
            [
                {field:'表项'},
                {field:'值'},
            ],
            defaultColDef: 
            {
                resizable: true,
            },
            rowData:
            [
                {'表项':'部门名称', '值':''},
                {'表项':'上级部门', '值':''},
            ]
        };

        new agGrid.Grid($$('grid_box'), grid_options);

        // 工具栏点击
        main_tb.events.on('click', function(id, e)
        {
            switch (id)
            {
                case '刷新':
                    window.location.reload();
                    break;
                case '提交':
                    upkeep_submit();
                    break;
                case '删除部门':
                    console.log('id=', selected_id);
                    delete_item();
                    break;
            }
        });

        function delete_item()
        {
            if (selected_id == '')
            {
                alert('请先选择部门');
                return;
            }
            if (dept_tree.data.haveItems(selected_id))
            {
                alert('"' + selected_id + '"' + ' 有下级部门,不能删除');
                return;
            }
            if (confirm('是否删除'))
            {
                alert('删除');
            }
        }

        function upkeep_submit(id)
        {
        }

        //tree event
        dept_tree.events.on('itemClick', function(id, e)
        {
            selected_id = id;

            var item = id.split('^');

            var items = [];
            items.push({'表项':'部门编码', '值':item[2]});
            items.push({'表项':'部门名称', '值':item[1]});
            items.push({'表项':'部门级别', '值':item[0]});
            if (item[0] == '1级')
            {
                items.push({'表项':'上级部门', '值':'无'});
            }
            else
            {
                items.push({'表项':'上级部门', '值':dept_tree.data.getParent(id).split('^')[1]});
            }
            if (dept_tree.data.haveItems(id))
            {
                items.push({'表项':'下级部门', '值':'有'});
            }
            else
            {
                items.push({'表项':'下级部门', '值':'无'});
            }

            grid_options.api.setRowData(items);
        });

    </script>

</body>
</html>