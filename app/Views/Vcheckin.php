<!-- v1.1.2.1.202207120955, from office -->
<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8'>
    <title>考勤</title>

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
    </style>

</head>

<body>
    <div id='main_tb'></div>
    <div class='condtion_box' id='csrbox'><b>选择人员</b></div>
    <div class='condtion_box' id='datebox'><b>选择日期</b></div>
    <div class='condtion_box' id='checkinbox'><b>选择考勤</b></div>

    <script type='text/javascript' charset='utf-8'>
        function $$(id)
        {
            return document.getElementById(id);
        }

        // 生成主菜单栏
        var main_tb = new dhx.Toolbar('main_tb', {css:'toobar-class'});
        main_tb.data.add({id:'刷新', type:'button', value:'刷新'});
        main_tb.data.add({id:'提交', type:'button', value:'提交'});

        var form = new dhx.Form('checkinbox',
        {
            css: 'dhx_widget--bordered',
            rows: [
            {
                type: 'select',
                name: '考勤类型',
                label: '考勤类型',
                labelWidth: '150px',
                width: '200px',
                options: 
                [
                    {
                        value: '病假',
                        content: '病假',
                    },
                    {
                        value: '事假',
                        content: '事假',
                    },
                    {
                        value: '法定年假',
                        content: '法定年假',
                    },
                    {
                        value: '补充年假',
                        content: '补充年假',
                    },
                    {
                        value: '婚假',
                        content: '婚假',
                    },
                    {
                        value: '产假',
                        content: '产假',
                    },
                    {
                        value: '陪产假',
                        content: '陪产假',
                    },
                    {
                        value: '产前检查假',
                        content: '产前检查假',
                    },
                    {
                        value: '哺乳假',
                        content: '哺乳假',
                    },
                    {
                        value: '丧假',
                        content: '丧假',
                    },
                    {
                        value: '迟到',
                        content: '迟到',
                    },
                    {
                        value: '早退',
                        content: '早退'
                    },
                ]
            },
            {
                type: 'input',
                name: '小时数',
                label: '小时数',
                width: '200px',
                //icon: 'dxi dxi-magnify',
                placeholder: '',
            }]
        });

        var csr_obj = JSON.parse('<?php echo $csr_json; ?>');
        var csr_tree = new dhx.Tree('csrbox', {checkbox: true});
        csr_tree.data.parse(csr_obj);

        var date_obj = JSON.parse('<?php echo $date_json; ?>');
        var date_tree = new dhx.Tree('datebox', {checkbox: true});
        date_tree.data.parse(date_obj);

        // 工具栏点击
        main_tb.events.on('click', function(id, e)
        {
            switch (id)
            {
                case '刷新':
                    window.location.reload();
                    break;
                case '提交':
                    checkin_submit();
                    break;
            }
        });

        function checkin_submit(id)
        {
            var checkin_obj = {};

            var state = form.getValue();

            checkin_obj['csr'] = csr_tree.getChecked();
            checkin_obj['date'] = date_tree.getChecked();
            checkin_obj['checkin'] = form.getValue();

            console.log('checkin_obj=', checkin_obj);

            dhx.ajax.post('<?php base_url(); ?>/duty/checkin_set/<?php echo $func_id; ?>', checkin_obj).then(function (data)
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