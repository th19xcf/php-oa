<!-- v1.2.1.1.202209292345, from surface -->
<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8'>
    <title>培训人员维护</title>

    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/dhtmlx/codebase/suite.css'>
    <script src='<?php base_url(); ?>/dhtmlx/codebase/suite.js'></script>

    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/ag-grid/dist/styles/ag-grid.css'>
    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/ag-grid/dist/styles/ag-theme-alpine.css'>
    <script src='<?php base_url(); ?>/ag-grid/dist/ag-grid-locale-cn.js'></script>
    <script src='<?php base_url(); ?>/ag-grid/dist/ag-grid-community.noStyle.js'></script>

    <script src='<?php base_url(); ?>/assets/js/datepicker_brower.js'></script>

    <style type='text/css'>
        div.float_box
        {
            width: 47%;
            height: 510px;
            margin: 4px;
            background-color: #f9f9f9;
            border: 1px solid #D0D0D0;
            box-sizing: border-box;
            float: left;
        }
    </style>
</head>

<body>
    <div id='main_tb' ></div>
    <div id='info_box' style='width:100%; height:10px; margin-bottom:3px; background-color: lightblue;'></div>
    <div class='float_box'>
        <div id='tree_box' style='height:100%;'></div>
    </div>

    <div class='float_box'>
        <div id='grid_box' class='ag-theme-alpine' style='width:100%; height:100%; background-color:lightblue;'></div>
    </div>

    <script type='text/javascript' charset='utf-8'>
        function $$(id)
        {
            return document.getElementById(id);
        }

        // tree视图
        var main_tb = new dhx.Toolbar('main_tb', {css:'toobar-class'});
        main_tb.data.add({id:'刷新', type:'button', value:'刷新'});
        main_tb.data.add({type:'separator'});
        main_tb.data.add({id:'修改培训信息', type:'button', value:'修改培训信息'});
        main_tb.data.add({id:'新增培训信息', type:'button', value:'新增培训信息'});
        main_tb.data.add({id:'更新培训结果', type:'button', value:'更新培训结果'});
        main_tb.data.add({type:'separator'});
        main_tb.data.add({id:'提交', type:'button', value:'提交'});
        main_tb.data.add({type:'spacer'});
        main_tb.data.add({id:'导入', type:'button', value:'导入'});

        $$('info_box').style.height = document.documentElement.clientHeight * 0.035 + 'px';
        $$('info_box').innerHTML = '&nbsp&nbsp选定人员:';

        var csr_str = '';
        var csr_guid = [];
        var csr_guid_query = '';
        var submit_type = '';
        var editable = false;
        var button = '';

        var tree_obj = JSON.parse('<?php echo $tree_json; ?>');
        var tree = new dhx.Tree('tree_box', {checkbox: true});
        tree.data.parse(tree_obj);

        //grid视图
        var grid_obj = JSON.parse('<?php echo $grid_json; ?>');
        const grid_options = 
        {
            columnDefs: 
            [
                {field:'表项', width:130, editable:false},
                {field:'值', width:280, cellEditorSelector:cellEditorSelector}
            ],
            defaultColDef: 
            {
                resizable: true,
                editable: (params) =>
                {
                    if (editable == false) return false;

                    // 根据配置判断是否可以修改
                    switch (params.data.表项)
                    {
                        case '属性':
                            return false;
                        default:
                            return true;
                    }
                },
            },
            components:
            {
                datePicker: get_date_picker(),
            },
            rowData: grid_obj,
        };

        new agGrid.Grid($$('grid_box'), grid_options);

        // 工具栏点击
        main_tb.events.on('click', function(id, e)
        {
            switch (id)
            {
                case '刷新':
                    window.location.reload();
                    editable = false;
                    button = '';
                    break;
                case '修改培训信息':
                    if (button != '查询培训信息')
                    {
                        alert('查询培训信息下, 才能修改');
                        return;
                    }
                    if (csr_guid.length == 0)
                    {
                        alert('请选择相关人员');
                        return;
                    }
                    else if (csr_guid.length > 1)
                    {
                        alert('只能选择一个人员');
                        return;
                    }

                    if (csr_guid[0] != csr_guid_query)
                    {
                        alert('选择人员和查询人员不符, 请重新选择')
                    }

                    var rowNode = grid_options.api.getRowNode(0);
                    rowNode.setDataValue('值', '修改培训信息');
                    submit_type = 'upkeep';
                    editable = true;
                    break;
                case '新增培训信息':
                    submit_type = 'insert';
                    editable = true;
                    button = '新增培训信息';
                    insert();
                    break;
                case '更新培训结果':
                    if (csr_guid.length == 0)
                    {
                        alert('请选择相关人员');
                        return;
                    }
                    submit_type = 'tran';
                    editable = true;
                    button = '更新培训结果';
                    tran();
                    break;
                case '提交':
                    if (submit_type == 'upkeep')
                    {
                        upkeep_submit();
                    }
                    else if (submit_type == 'tran')
                    {
                        tran_submit();
                    }
                    else if (submit_type == 'insert')
                    {
                        insert_submit();
                    }
                    break;
                case '导入':
                    parent.window.goto('<?php echo $import_func_id; ?>','导入-'+'<?php echo $import_func_name; ?>','Upload/init/<?php echo $import_func_id; ?>');
                    break;
            }
        });

        //tree event
        tree.events.on('itemClick', function(id, e)
        {
            dhx.ajax.post('<?php base_url(); ?>/train/ajax/<?php echo $func_id; ?>', id).then(function (data)
            {
                grid_obj = JSON.parse(data);
                grid_options.api.setRowData(grid_obj);
                editable = false;
                button = grid_obj[0]['值'];
                var item = id.split('^');
                csr_guid_query = item[1];
            }).catch(function (err)
            {
                console.log('err=', err);
                alert('失败, ' + " " + err.statusText);
            });
        });

        tree.events.on('afterCheck', function (index, id, value)
        {
            csr_str = '';
            csr_guid = [];

            item = [];
            selected_arr = tree.getChecked();
            for (var ii in selected_arr)
            {
                item = selected_arr[ii].split('^');
                if (item[0] != '人员')
                {
                    continue;
                }
                if (csr_str!='') csr_str = csr_str + ',';
                csr_str = csr_str + item[2];
                csr_guid.push(item[1]);
            }
            $$('info_box').innerHTML = '&nbsp&nbsp选定人员:' + csr_str;
        });

        //grid event
        function cellEditorSelector(params)
        {
            var col_name = params.data.列名;

            switch (params.data.表项)
            {
                case '培训结果':
                    return {
                        component: 'agSelectCellEditor',
                        params: {
                            values: ['','通过','未通过','离开','淘汰','转期']
                        },
                    };
                case '属地':
                    return {
                        component: 'agSelectCellEditor',
                        params: {
                            values: ['','北京总公司','河北分公司','四川分公司']
                        },
                    };
                case '招聘渠道':
                    return {
                        component: 'agSelectCellEditor',
                        params: {
                            values: ['','校招','社招']
                        },
                    };
                case '培训业务':
                    var object_obj = JSON.parse('<?php echo $object_json; ?>');
                    return {
                        component: 'agSelectCellEditor',
                        params: {
                            values: object_obj['培训业务']
                        },
                    };
                case '住宿':
                    return {
                        component: 'agSelectCellEditor',
                        params: {
                            values: ['','是','否']
                        },
                    };
                case '培训开始日期':
                case '预计完成日期':
                case '培训完成日期':
                case '培训离开日期':
                case '培训结束日期':
                    return {
                        component: 'datePicker',
                    };
                case '邀约结果':
                    return {
                        component: 'agSelectCellEditor',
                        params: {
                            values: ['','通过','未通过','考虑']
                        },
                    };
            }
        }

        // 更新培训信息
        function upkeep_submit(id)
        {
            if (csr_guid.length == 0)
            {
                alert('请选择相关人员');
                return;
            }
            else if (csr_guid.length > 1)
            {
                alert('只能选择一个人员');
                return;
            }

            var ajax = 0;

            grid_options.api.stopEditing();
            grid_options.api.forEachNode((rowNode, index) =>
            {
                if (rowNode.data['表项'] == '属性' && rowNode.data['值'] != '修改培训信息')
                {
                    alert('请点选修改培训信息选项进行相关操作');
                    ajax = -1;
                }
            });

            if (ajax == -1)
            {
                return;
            }

            var arg_obj = {};
            arg_obj['操作'] = '修改记录';
            arg_obj['人员'] = csr_guid;

            grid_options.api.forEachNode((rowNode, index) =>
            {
                if (rowNode.data['表项'] != '属性')
                {
                    arg_obj[rowNode.data['表项']] = rowNode.data['值'];
                    if (rowNode.data['值'] != '')
                    {
                        ajax = 1;
                    }
                }
            });

            if (ajax == 0)
            {
                alert('请输入要更改的信息');
                return;
            }

            dhx.ajax.post('<?php base_url(); ?>/train/upkeep/<?php echo $func_id; ?>', arg_obj).then(function (data)
            {
                alert('修改成功');
            }).catch(function (err)
            {
                alert('修改失败, ' + " " + err.statusText);
            });
        }

        // 转入正式表
        function tran()
        {
            rowData = 
            [
                {'表项':'属性', '值':'更新培训结果'},
                {'表项':'培训结果', '值':''},
                {'表项':'培训结束日期', '值':''},
                {'表项':'账号', '值':''},
                {'表项':'工号', '值':''},
            ];

            grid_options.api.setRowData(rowData);
        }

        // 参培标识更改提交
        function tran_submit(id)
        {
            if (csr_guid == false)
            {
                alert('请选择相关人员');
                return;
            }

            var ajax = 0;

            grid_options.api.stopEditing();
            grid_options.api.forEachNode((rowNode, index) =>
            {
                if (rowNode.data['表项'] == '属性' && rowNode.data['值'] != '更新培训结果')
                {
                    alert('请点选更新培训结果按钮进行更新操作');
                    ajax = -1;
                }
            });

            if (ajax == -1)
            {
                return;
            }

            var arg_obj = {};
            arg_obj['操作'] = '更新培训结果';
            arg_obj['人员'] = csr_guid;

            grid_options.api.forEachNode((rowNode, index) =>
            {
                if (rowNode.data['表项'] != '属性')
                {
                    arg_obj[rowNode.data['表项']] = rowNode.data['值'];
                    if (rowNode.data['表项']=='培训结果' && rowNode.data['值'] == '')
                    {
                        alert('"参培结果"为空,请补充.');
                        ajax = -1;
                    }
                    if (rowNode.data['表项']=='培训结束日期' && rowNode.data['值'] == '')
                    {
                        alert('"培训结束日期"为空,请补充.');
                        ajax = -1;
                    }
                }
            });

            if (ajax == -1)
            {
                return;
            }

            dhx.ajax.post('<?php base_url(); ?>/train/tran/<?php echo $func_id; ?>', arg_obj).then(function (data)
            {
                alert('修改成功');
                window.location.reload();
            }).catch(function (err)
            {
                alert('修改失败, ' + " " + err.statusText);
            });
        }

        function insert()
        {
            rowData = 
            [
                {'表项':'属性', '值':'新增培训信息'},
                {'表项':'姓名', '值':''},
                {'表项':'身份证号', '值':''},
                {'表项':'手机号码', '值':''},
                {'表项':'属地', '值':''},
                {'表项':'招聘渠道', '值':''},
                {'表项':'实习结束日期', '值':''},
                {'表项':'渠道名称', '值':''},
                {'表项':'信息来源', '值':''},
                {'表项':'面试业务', '值':''},
                {'表项':'面试岗位', '值':''},
                {'表项':'面试日期', '值':''},
                {'表项':'面试结果', '值':''},
                {'表项':'面试人', '值':''},
                {'表项':'预约培训日期', '值':''},
                {'表项':'住宿', '值':''},
                {'表项':'备注说明', '值':''},
                {'表项':'参培信息', '值':''},
            ];

            grid_options.api.setRowData(rowData);
        }

        // 新增记录提交
        function insert_submit(id)
        {
            var ajax = 0;

            grid_options.api.stopEditing();
            grid_options.api.forEachNode((rowNode, index) =>
            {
                if (rowNode.data['表项'] == '属性' && rowNode.data['值'] != '新增培训信息')
                {
                    alert('请点选新增培训信息选项进行相关操作');
                    ajax = -1;
                }
            });

            if (ajax == -1)
            {
                return;
            }

            var arg_obj = {};
            arg_obj['操作'] = '新增培训信息';

            grid_options.api.forEachNode((rowNode, index) =>
            {
                if (rowNode.data['表项'] != '属性')
                {
                    arg_obj[rowNode.data['表项']] = rowNode.data['值'];
                    if (rowNode.data['值'] != '')
                    {
                        ajax = 1;
                    }
                }
            });

            if (ajax == 0)
            {
                alert('请输入新增记录相关信息');
                return;
            }

            dhx.ajax.post('<?php base_url(); ?>/train/insert/<?php echo $func_id; ?>', arg_obj).then(function (data)
            {
                alert('新增记录成功');
                window.location.reload();
            }).catch(function (err)
            {
                alert('新增记录失败, ' + " " + err.statusText);
            });
        }

    </script>

</body>
</html>
