<!-- v1.1.1.1.202204141310, from office -->
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
    <a href='<?php base_url(); ?>/Template/学校信息表.xlsx'><u>下载模板</u></a>
    <br/>
    <div id='uploader' class='easy-upload' style='margin-top:30px;'></div>

    <script type='text/javascript' charset='utf-8'>

        var url = '<?php echo $import_page; ?>';

        var uploader = easyUploader(
        {
            id: 'uploader',
            accept: '.xlsx,.xls',
            action: url,
            dataFormat: 'formData',
            maxCount: 1,
            maxSize: 100,
            multiple: true,
            name: 'upfiles',
            data: null,
            beforeUpload: function(file, data, args)
            {
                /* dataFormat为formData时配置发送数据的方式 */
                data.append('token', '387126b0-7b3e-4a2a-86ad-ae5c5edd0ae6TT');
                data.append('otherKey', 'otherValue');
                data.append('func_id', '<?php echo $func_id; ?>');
                console.log('data', data);
            },
            onChange: function(fileList)
            {
                /* input选中时触发 */
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