<!-- v1.2.2.0.202306241050, from home -->
<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8'>
    <title>upload</title>

    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/easyupload/main.css'>
    <script src='<?php base_url(); ?>/easyupload/easyUploader.jq.js'></script>
</head>

<body>
    <br/>
    <h3>模板下载</h3>
    <a href='<?php base_url(); ?>/Template/<?php echo $tmpl_file; ?>'><u>下载模板</u></a>
    <br/>
    <div id='month_div'>
        <h3>输入工作月份</h3>
        <input type='text' id='work_month' />(格式:2022-04)
    </div>
    <div id='date_div'>
        <h3>输入工作日期</h3>
        <input type='date' id='work_date' />
    </div>

    <br/>
    <div id='model_div'>
        <h3>选择数据导入模式</h3>
        <fieldset>
            <legend></legend>

            <div>
                <input type='radio' id='insert' name='model' value='insert' checked>
                <label for='insert'>新增</label>
                <input type='radio' id='update' name='model' value='update'>
                <label for='update'>更新</label>
            </div>
            <br/>
            <div>
                <label for='key_field'>输入索引字段名称：</label><input type='text' id='key_field' name='key_field' value=''>
            </div>
        </fieldset>
    </div>

    <br/>
    <h3>上传文件</h3>
    <div id='uploader' class='easy-upload' style='margin-top:30px;'></div>

    <script type='text/javascript' charset='utf-8'>
        function $$(id)
        {
            return document.getElementById(id);
        }

        var month_block = '<?php echo $work_month; ?>';
        var date_block = '<?php echo $work_date; ?>';
        var model_block = '<?php echo $upload_model; ?>';

        if (month_block == '')
        {
            $$('month_div').style.display = 'none';
        }
        if (date_block == '')
        {
            $$('date_div').style.display = 'none';
        }
        if (model_block == '')
        {
            $$('model_div').style.display = 'none';
        }

        var uploader = easyUploader(
        {
            id: 'uploader',
            accept: '.xlsx,.xls',
            action: '<?php echo $import_page; ?>',
            dataFormat: 'formData',
            maxCount: 1,
            maxSize: 100,
            multiple: true,
            name: 'upfiles',
            data: null,
            beforeUpload: function(file, data, args)
            {
                var radio = document.getElementsByName('model');
                var radio_value = '';
                for (var i = 0; i < radio.length; i++)
                {
                    if (radio[i].checked)
                    {
                        radio_value = radio[i].value;
                        break;
                    }
                }

                var fld_value = $$('key_field').value;

                if (radio_value == 'update' && fld_value == '')
                {
                    alert('请输入索引字段名称');
                    return false;
                }

                /* dataFormat为formData时配置发送数据的方式 */
                data.append('token', '387126b0-7b3e-4a2a-86ad-ae5c5edd0ae6TT');
                data.append('otherKey', 'otherValue');
                data.append('func_id', '<?php echo $func_id; ?>');
                data.append('work_month', $$('work_month').value);
                data.append('work_date', $$('work_date').value);
                data.append('model', radio_value);
                data.append('key_field', fld_value);
            },
            onChange: function(fileList)
            {
                /* input选中时触发 */
                if (month_block != '' && $$('work_month').value =='')
                {
                    alert('请输入工作月份');
                }
                if (date_block != '' && $$('work_date').value =='')
                {
                    alert('请输入工作日期');
                }
            },
            onRemove: function(removedFiles, files)
            {
                // console.log('onRemove', removedFiles);
            },
            onSuccess: function(res)
            {
                if (res.status == 200)
                {
                    $('.progress-text').text('成功');
                    alert('上传成功！！');
                    window.location.reload();
                }
                else
                {
                    alert(res.msg)
                }
            },
            onError: function(err)
            {
                console.log('err', err.responseText);
                $('.progress-text').text('失败');
                alert('onError，请联系管理员', err);
                window.location.reload();
            },
        });
    </script>

</body>

</html>