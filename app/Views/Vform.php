<!DOCTYPE html>
<html style="display: block;">

<head>
    <meta charset="utf-8">
    <title>表单测试</title>
</head>

<body>
    <h2>表单</h2>
    <?php echo form_open($NextPage); ?>
        姓名：
        <input type='text' name='name'>
        <input type='submit' value='提交'>
    <?php echo form_close(); ?>
</body>

</html>