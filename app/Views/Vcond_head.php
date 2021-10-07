<!-- v1.0.0.1.202110071730, from home -->
<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8'>
    <title><?php echo $title; ?></title>
    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/assets/css/biz.css'>
    <script src="<?php base_url(); ?>/assets/js/jquery.js"></script>

    <script type="text/javascript">
        $(document).ready(function()
        {
            $("#good").click(function()
            {
                $.ajax(
                {
                    type: "POST",
                    url: "<?php echo $NextPage; ?>",
                    data: $("form").serialize(),
                    cache: false,
                    success: function(r)
                    {
                        alert('succ' + r);
                    },
                    error: function(r)
                    {
                        alert('error' + r);
                    }
                });
            });
        });
    </script>
</head>
