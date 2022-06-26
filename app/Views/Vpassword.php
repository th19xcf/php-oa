<!-- v1.1.1.1.202206261345, from home -->
<!DOCTYPE html>
<html style="display: block;">

<head>
    <meta charset="utf-8">
    <title>修改密码</title>
</head>

<body>
    <script type="text/javascript">
        function $(id)
        {
            return document.getElementById(id);
        }

        function dosubmit()
        {
            if ($('pswd_1').value != $('pswd_2').value)
            {
                alert('两次密码不同, 请重新输入');
                return false;
            }
        }
    </script>

    <h2><?php echo $title; ?></h2>
    <form name='pswd' action='<?php base_url(); ?>/<?php echo $next_page; ?>/front' onsubmit='return dosubmit()' method='post'>
        新密码：
        <input type='password' id='pswd_1' name='pswd_1'>
        再次输入新密码：
        <input type='password' id='pswd_2' name='pswd_2'>
        <input type='submit' value='提交'>
    </form>
</body>

</html>