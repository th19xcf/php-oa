<!-- v1.1.1.1.202205311600, from office -->
<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8'>
    <title>排班</title>

    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/dhtmlx/codebase/suite.css'>
    <script src='<?php base_url(); ?>/dhtmlx/codebase/suite.js'></script>

    <style type='text/css'>
        div.condtion_box
        {
            width: 31%;
            height: 570px;
            margin: 10px;
            background-color: #f9f9f9;
            border: 1px solid #D0D0D0;
            box-sizing: border-box;
            float: left;
        }
        div.result_box
        {
            width: 100%;
            height: 570px;
            margin-top: 10px;
            background-color: #f9f9f9;
            border: 1px solid #D0D0D0;
            box-sizing: border-box;
            float: left;
        }
    </style>

</head>

<body>
    <div id='main_tb'></div>
    <div class='condtion_box' id='csrbox'><b>选择人员</b></div>
    <div class='condtion_box' id='datebox'><b>选择日期</b></div>
    <!--<div class='condtion_box' id='taskbox'><b>选择业务</b></div>-->
    <div class='condtion_box' id='dutybox'><b>选择班务</b></div>
    <div class='result_box' id='resultbox'></div>

    <script type='text/javascript' charset='utf-8'>
        function $$(id)
        {
            return document.getElementById(id);
        }

        $$('resultbox').style.display = 'none';

        // 生成主菜单栏
        var main_tb = new dhx.Toolbar('main_tb', {css:'toobar-class'});
        main_tb.data.add({id:'刷新', type:'button', value:'刷新'});
        main_tb.data.add({id:'提交', type:'button', value:'提交'});

        var csr_obj = JSON.parse('<?php echo $csr_json; ?>');
        var csr_tree = new dhx.Tree('csrbox', {checkbox: true});
        csr_tree.data.parse(csr_obj);

        var date_obj = JSON.parse('<?php echo $date_json; ?>');
        var date_tree = new dhx.Tree('datebox', {checkbox: true});
        date_tree.data.parse(date_obj);

        /*
        var task_obj = JSON.parse('<?php echo $task_json; ?>');
        var task_tree = new dhx.Tree('taskbox', {checkbox: true});
        task_tree.data.parse(task_obj);
        */

        var duty_obj = JSON.parse('<?php echo $duty_json; ?>');
        var duty_tree = new dhx.Tree('dutybox', {checkbox: true});
        duty_tree.data.parse(duty_obj);

        // 工具栏点击
        main_tb.events.on('click', function(id, e)
        {
            switch (id)
            {
                case '刷新':
                    window.location.reload();
                    break;
                case '提交':
                    tree_submit();
                    break;
            }
        });

        function tree_submit(id)
        {
            var sch_arr = [];
            var csr_arr = csr_tree.getChecked();
            var date_arr = date_tree.getChecked();
            var duty_arr = duty_tree.getChecked();
    
            sch_arr['csr'] = csr_arr;
            sch_arr['date'] = date_arr;
            sch_arr['duty'] = duty_arr;

            console.log(sch_arr);
            dhx.ajax.post('<?php base_url(); ?>/Scheduling/set/<?php echo $func_id; ?>', sch_arr).then(function (data)
            {
                alert('新增记录成功');
            }).catch(function (err)
            {
                alert('新增记录错误, ' + " " + err.statusText);
            });
        }

    </script>

</body>
</html>