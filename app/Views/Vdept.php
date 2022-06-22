<!-- v1.1.0.1.2022060221930, from home -->
<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8'>
    <title>部门</title>

    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/dhtmlx/codebase/suite.css'>
    <script src='<?php base_url(); ?>/dhtmlx/codebase/suite.js'></script>

    <style type='text/css'>
        div.condtion_box
        {
            width: 46%;
            height: 570px;
            margin: 10px;
            background-color: #f9f9f9;
            border: 1px solid #D0D0D0;
            box-sizing: border-box;
            float: left;
        }
    </style>

</head>

<body>
    <div id='main_tb'></div>
    <div class='condtion_box' id='deptbox'><b>组织架构</b></div>

    <script type='text/javascript' charset='utf-8'>
        function $$(id)
        {
            return document.getElementById(id);
        }

        // 生成主菜单栏
        var main_tb = new dhx.Toolbar('main_tb', {css:'toobar-class'});
        main_tb.data.add({id:'刷新', type:'button', value:'刷新'});
        main_tb.data.add({id:'提交', type:'button', value:'提交'});

        var dept_obj = JSON.parse('<?php echo $dept_json; ?>');
        var dept_tree = new dhx.Tree('deptbox', {checkbox: true});
        dept_tree.data.parse(dept_obj);

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
            }
        });

        function upkeep_submit(id)
        {
        }

    </script>

</body>
</html>