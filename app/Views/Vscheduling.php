<!-- v1.3.2.1.202207101800, from home -->
<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8'>
    <title>排班</title>

    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/dhtmlx/codebase/suite.css'>
    <script src='<?php base_url(); ?>/dhtmlx/codebase/suite.js'></script>

    <style type='text/css'>
        div.float_box
        {
            width: 31%;
            height: 530px;
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
    <div class='float_box'>
        <div id='csrbox' style='height:95%;'><b>选择人员</b></div>
    </div>
    <div class='float_box'>
        <div id='datebox' style='height:95%;'><b>选择日期</b></div>
    </div>
    <div class='float_box'>
        <div id='dutybox' style='height:95%;'><b>选择班务</b></div>
    </div>

    <script type='text/javascript' charset='utf-8'>
        function $$(id)
        {
            return document.getElementById(id);
        }

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
            var sch_obj = {};

            sch_obj['csr'] = csr_tree.getChecked();
            sch_obj['date'] = date_tree.getChecked();
            sch_obj['duty'] = duty_tree.getChecked();

            dhx.ajax.post('<?php base_url(); ?>/duty/scheduling_set/<?php echo $func_id; ?>', sch_obj).then(function (data)
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