<!-- v1.3.0.1.202110210030, from home -->
<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8'>
    <title>htmlx</title>
    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/dhtmlx/codebase/suite.css'>
    <script src='<?php base_url(); ?>/dhtmlx/codebase/suite.js'></script>

    <style>
    </style>

    <script type='text/javascript'>
        // 获得对象
        function $$(id)
        {
            return document.getElementById(id);
        }

        function doOnLoad()
        {
            // 生成主菜单栏
            var main_tb = new dhx.Toolbar('toolbarbox', {css: 'toobar-class'});
            main_tb.data.add({id:'名称', type:'title', value:'主菜单-->'});
            main_tb.data.add({id:'条件查询', type:'button', value:'条件查询'});
            main_tb.data.add({id:'列选择', type:'button', value:'列选择'});
            main_tb.data.add({type:'separator'});
            main_tb.data.add({id:'刷新', type:'button', value:'刷新'});

            $$('condbox').style.height = document.documentElement.clientHeight*0.84 + 'px';
            $$('gridbox').style.height = document.documentElement.clientHeight*0.84 + 'px';
            $$('footbox').style.height = document.documentElement.clientHeight*0.04 + 'px';

            $$('condbox').style.display = 'none';

            // 数据表列信息
            var data_column_obj = JSON.parse('<?php echo $column_json; ?>');
            //console.log(data_column_obj);

            var data_columns_arr = [];
            var column_select_arr = [{'type':'checkbox','name':'全选','text':'全选','checked':false},{'type':'checkbox','name':'全不选','text':'全不选','checked':false}];
            for (var key in data_column_obj)
            {
                var col_obj = {};
                col_obj['id'] = data_column_obj[key].id;
                col_obj['header'] = [];
                col_obj['header'].push(data_column_obj[key].header);

                data_columns_arr.push(col_obj);

                var col_obj = {};
                col_obj['type'] = 'checkbox';
                col_obj['name'] = data_column_obj[key].id;
                col_obj['text'] = data_column_obj[key].id;
                col_obj['checked'] = true;
                column_select_arr.push(col_obj);
            }

            console.log(column_select_arr);

            // 生成数据grid
            var data_grid = new dhx.Grid('gridbox', {columns:data_columns_arr});

            // 加载数据
            var data_grid_obj = JSON.parse('<?php echo $data_json; ?>');
            //console.log('数据:',data_grid_obj);
            data_grid.data.parse(data_grid_obj);

            // 生成条件菜单栏
            cond_tb = new dhx.Toolbar('cond_toolbar', {css: 'toobar-class'});
            cond_tb.data.add({id:'名称', type:'title', value:' 主菜单-->条件查询-->'});
            cond_tb.data.add({id:'重置', type:'button', value:'重置'});
            cond_tb.data.add({id:'查询', type:'button', value:'查询'});

            // 生成条件grid
            var cond_grid = new dhx.Grid('cond_grid', 
            {
                columns:
                [
                    {id:'列名', header:[{text:'列名'}], editable:false },
                    {id:'类型', header:[{text:'类型'}], editable:false },
                    {id:'汇总', header:[{text:'汇总'}], type:'boolean' },
                    {id:'平均', header:[{text:'平均'}], type:'boolean' },
                    {id:'条件1', header:[{text:'条件1'}], editorType:'select', options:['','大于','等于','小于','大于等于','小于等于','不等于','包含','不包含'] },
                    {id:'参数1', header:[{text:'参数1'}] },
                    {id:'条件关系', header:[{text:'条件关系'}], editorType:'select', options:['','并且','或者'] },
                    {id:'条件2', header:[{text:'条件2'}], editorType:'select', options:['','大于','等于','小于','大于等于','小于等于','不等于','包含','不包含'] },
                    {id:'参数2', header:[{text:'参数2'}] }
                ],
                editable: true
            });

            // 加载数据
            var cond_grid_obj = JSON.parse('<?php echo $cond_json; ?>');
            //console.log('条件:', cond_grid_obj);
            cond_grid.data.parse(cond_grid_obj);

            // 工具栏点击
            main_tb.events.on('click', function(id, e)
            {
                switch (id)
                {
                    case '条件查询':  
                        if ($$('condbox').style.display=='block')
                        {
                            $$('condbox').style.display = 'none';
                            $$('gridbox').style.display = 'block';

                            break;
                        }

                        $$('condbox').style.display = 'block';
                        $$('gridbox').style.display = 'none';

                        break;

                    case '列选择':
                        tb_column_click();
                        break;
                }
            });

            cond_tb.events.on('click', function(id, e)
            {
                var post_data = [];

                if (id=='重置')
                {
                    cond_grid.data.removeAll();
                    cond_grid_obj = JSON.parse('<?php echo $cond_json; ?>');
                    cond_grid.data.parse(cond_grid_obj);
                }
                else if (id=='查询')
                {
                    var cond_json = [];

                    var cond_str = '';
                    var group_str = '';
                    var average_str = '';

                    // 条件关系检查
                    var average = false;
                    var group = false;
                    var ajax = true;

                    cond_grid.data.forEach(function(element, index, array)
                    {
                        if (element['平均'] == true)
                        {
                            average = true;
                            average_str = element['列名'];
                        }
                        else if (element['汇总'] == true)
                        {
                            group = true;
                            group_str = element['列名'];
                        }
                        else if (element['参数1'].length>0 && element['条件1']=='')
						{
                            alert('条件1设置不正确');
                            ajax = false;
                        }
                        else if (element['参数2'].length>0 && element['条件2']=='')
						{
                            alert('条件2设置不正确');
                            ajax = false;
                        }
                        else if (element['条件1']!='' && element['条件2']!='' && element['条件关系']=='')
                        {
                            alert('条件关系设置不正确');
                            ajax = false;
                        }

                        if (element['汇总']==true || element['平均']==true || element['条件1']!='')
                        {
                            cond_json.push(element);

                            if (element['条件1'] != '')
                            {
                                cond_str = element['列名'] + element['条件1'] + element['参数1'];
                            }
                            if (element['条件2'] != '')
                            {
                                cond_str = cond_str + element['条件关系'] + element['条件2'] + element['参数2'];
                            }
                        }
                    });

                    if (ajax == false)
                    {
                        return;
                    }

                    if (average==true && group==false)
                    {
                        alert('计算平均值, 必须设置汇总字段');
                        return;
                    }

                    $$('footbox').innerHTML = '条件:{' + cond_str + '} , 汇总:{' + group_str + '} , 平均:{' + average_str + '}';

                    // 条件设置正确
                    dhx.ajax.post('<?php base_url(); ?>/Frame/set_condition/<?php echo $func_id; ?>', cond_json).then(function (data)
                    {
                        data_grid.data.removeAll();
                        data_grid_obj = JSON.parse(data);
                        data_grid.data.parse(data_grid_obj);

                        $$('condbox').style.display = 'none';
                        $$('gridbox').style.display = 'block';
                    }).catch(function (err)
                    {
                        console.log('status' + " " + err.statusText);
                    });
                }           
            });

            function tb_column_click()
            {
                var win = new dhx.Window(
                {
                    title: '列选择窗口',
                    modal: true,
                    width: 300,
                    height: 520,
                    closable: true,
                    movable: true
                });

                var form = new dhx.Form('column',
                {
                    rows: column_select_arr
                });

                win.attach(form);
                win.show();

                form.events.on('change', function(name, value)
                {
                    for (var key in column_select_arr)
                    {
                        if (name == '全选')
                        {
                            column_select_arr[key]['checked'] = true;
                            //form.getItem(column_select_arr[key]['id']).setValue(true);
                        }
                        else if (name == '全不选')
                        {
                            column_select_arr[key]['checked'] = false;
                            //form.getItem(column_select_arr[key]['id']).setValue(false);
                        }
                        else if (column_select_arr[key]['name'] == name)
                        {
                            column_select_arr[key]['checked'] = value;
                            if (value == true) data_grid.showColumn(name);
                            else data_grid.hideColumn(name);
                            break;
                        }
                    }
                    column_select_arr[0]['checked'] = false;
                    column_select_arr[1]['checked'] = false;
                    console.log(column_select_arr);
                });
            }
        }

    </script>
</head>

<body onload='doOnLoad();'>
    <div id='toolbarbox'></div>
    <div id='condbox' style='width:100%; height:600px; background-color:lightblue;'>
        <div id='cond_toolbar'></div>
        <div id='cond_grid' style='width:100%; height:92%; background-color:blue;'></div>
    </div>
    <div id='gridbox' style='width:100%; height:600px; background-color:lightblue;'></div>
    <div id='footbox' style='width:100%; height:10px; margin-top:5px; background-color: lightblue;'></div>
</body>

</html>