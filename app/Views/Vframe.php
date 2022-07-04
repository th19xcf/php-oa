<!-- v1.2.1.1.202207041455, from office -->
<!DOCTYPE html>
<html>
<head>
    <title>客服运营管理系统</title>
    <base href="<?php echo base_url()?>"/>
    <link rel="stylesheet" type="text/css" href="assets/css/indexcss.css">
    <link rel="stylesheet" type="text/css" href="assets/css/treeview.css">
    <script src="assets/js/jquery.js"></script>
    <script src="assets/js/common.js"></script>
    <script src="assets/js/treeview.js"></script>

    <style type="text/css">
        .c
        {
            clear: both;
        }

        #div_ntcinfo
        {
            top: 65px !important;
            position: absolute;
            right: 1px;
            width: 170px;
            z-index: 1000;
            border-left: 2px solid #d71920;
            background: #fff;
        }

        #div_ntype
        {
            height: 20px;
            margin-top: 2px;
            margin-bottom: 8px;
            border-bottom: 2px solid #D71920;
            width: 170px;
        }

        #div_ntype ul li
        {
            float: left;
            padding: 3px 2px 0 2px;
            height: 15px;
            border: 1px solid #D71920;
            border-top-right-radius: 4px 4px;
            border-top-left-radius: 4px 4px;
            cursor: pointer;
            font-size: 9pt;
            font-family: 'Microsoft YaHei';
            margin-left: 3px;
            background: url(assets/css/images/tabs-item-over-bg.gif);
            color: #fff;
        }

        #div_ntype ul li span
        {
            height: 15px;
            display: inline-block;
            line-height: 17px;
        }

        #iframemsg
        {
            width: 100%;
            height: 525px;
            border: 0px;
            overflow-y: auto;
        }

        .nav-dot.active
        {
            border: 1px solid #f00;
            -webkit-border-radius: 4px;
            background-image: -webkit-linear-gradient(#F6CBBC,#FF341F);
        }

        .nav-dot
        {
            width: 6px;
            height: 6px;
            position: absolute;
            top: 0;
        }

        .g-dlgBox.bottom {
            top: auto !important;
        }
    </style>

</head>

<body>
    <div id="header">
        <div class="sysName">
            <b>北京电信发展有限公司</b>
        </div>
        <div class="toolBar">
            <a id="lk_resetpwd" href="javascript:void(0);" tag="Frame/change_pswd" onClick="goto(-100)" title="修改密码">[修改密码]</a>
            <a id="lk_exit" href="javascript:onLoginOut();" title="安全退出">[安全退出]</a>
        </div>

        <!--左面板控制-->
        <div style="position: absolute; top: 39px; left: 0px;">
            <a id="lSidebar_img" href="javascript:void(0);" class="collapsed_yes"></a>
        </div>

        <!--右面板控制-->
        <div style="position: absolute; top: 39px; right: 0px;">
            <a id="rSidebar_img" href="javascript:void(0);" class="collapsed_no"></a>
        </div>

        <!--页面选项卡 start-->
        <div class="tabMain">
            <ul><li tags="-1" class="tabMain_ul_li_curr"><span>首页</span></li></ul>
        </div>
    </div>

    <div id="h_split_line" style="height: 4px; background-color: #E91E63; border-bottom: 1px solid #D71920;"></div>

    <style>
        #praise
        {
            display:block;
            font-size:17px;
            width:500px;
            height:300px;
            background-image:url(assets/css/images/award.jpg);
            background-repeat:no-repeat;
            background-position:center;
            background-size:cover;
        }
    </style>

    <div id="MainMiddle">
        <div style="float:right; margin-left:-173px; width:100%;">
            <div id="MRight" style="margin-left: 174px; margin-right: 0px;">
                <div id="praise" style="display:block;"></div>
                <div id="loser" style="display:block; font-size:20px;"></div>
                <div class="mainTab" id="mainTab-1" style="overflow: auto; z-index: 999; display: block;">
                    <iframe id="iframe-1" name="iframe-1" border="0" frameborder="0" style="height:606px; width:1083px;"></iframe>
                </div>
            </div>
        </div>

        <div id="MLeft" style="height: 634px;">
            <ul id="resTree" class="treeview"> </ul>
        </div>
    </div>

    <div class="c"></div>

    <div id="hideIframe" class="hide"></div>

    <!--默认页面链接-->
    <div style="display:none;">
        <a id="deflink" href="javascript:void(0);" tag="Frame/UserMis" onClick="goto(-1, &#39;首页&#39;, &#39;Frame/UserMis&#39;);">首页</a>
    </div>

    <!--消息提示弹窗-->
    <div id="win_notify" style="width:100%; height:40px; display:block;">
        <table style="width:100%;">
            <tbody>
                <tr>
                    <td style="text-align: center;">
                        <a href="javascript:void(0);" onClick="goto(3201, &#39;我的消息&#39;, &#39;/msg/chat&#39;, 1); $(&#39;#win_notify&#39;).closeDialog();">您有<span id="win_notify_msg_ct"></span>条新消息，请及时查看</a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <script type="text/javascript">
        /*公共变量*/
        var gCurUsr = 
        {
            //当前用户信息
            Company_id: sessionStorage.getItem('team_id'),
            Emp_id: sessionStorage.getItem('person_id'),
            User_id: sessionStorage.getItem('agent_id'),
            Uid_type: "1",
            Station: "11"
        };

        var gFrameWidth = 0;//页面iframe宽度
        var gIsBaseSmpl = 1;

        /*树点击: resname,resurl 可为空*/
        function goto(res_id, resname, resurl, refresh)
        {
            var res_name = "", res_url = "";
            if (typeof (resurl) == 'undefined')
            {
                var evt = window.event;
                var src = evt.srcElement || evt.target;
                res_name = src.innerText;
                res_url = src.attributes["tag"].value;
            }
            else
            {
                res_name = resname;
                res_url = resurl;
            }

            var $tabUl = $(".tabMain>ul");

            //创建标签
            if ($("li[tags=" + res_id + "]", $tabUl).length == 0)
            {
                if ($("li", $tabUl).length >= 12) 
                {
                    $.msg.warn("您打开的标签过多!");
                    return; 
                }

                $tabUl.append("<li tags='" + res_id + "'><span>" + res_name + "</span><a href='javascript:void(0);'></a></li>");
                $("#MRight").append("<div class='mainTab' id='mainTab" + res_id + "' style='overflow:auto; z-index:999;'><iframe id='iframe" + res_id + "' name='" + res_name + "' border='0' frameborder='0'></iframe></div>");
                $("#iframe" + res_id).height($(window).height() - 65).width(gFrameWidth);
                document.getElementById("iframe" + res_id).src = res_url;
                //$.get("Frame/Pointlog", { name:res_name, url:res_url }); //点击量
            }
            else if (typeof (refresh) != 'undefined' && refresh == 1) 
            {
                //强制刷新
                document.getElementById("iframe" + res_id).src = res_url + (res_url.indexOf('?') > -1 ? "&_=" : "?_=") + Math.random();
            }

            //切换标签（执行li click）
            $("li", $tabUl).attr("class", "tabMain_ul_li");
            $("li[tags=" + res_id + "]", $tabUl).attr("class", "tabMain_ul_li_curr");
            $("#MRight>div").hide();
            $("#mainTab" + res_id).show();
        }

        /*点击页面选项卡*/
        function tabSelect() 
        {
            var $parent = $(this).parent();
            var tags = $parent.attr("tags");
            $("div.tabMain li").attr("class", "tabMain_ul_li");
            $parent.attr("class", "tabMain_ul_li_curr");
            $("#MRight>div").hide();
            $("#mainTab" + tags).show();
        }

        /*关闭页面选项卡*/
        function tabClose()
        {
            //删除
            var $parent = $(this).parent();
            var tags = $parent.attr("tags");
            var ind = $parent.index() - 1;
            $("#mainTab" + tags).remove();
            $parent.remove();
            var cu = $parent.attr("class");

            if (cu == "tabMain_ul_li_curr")
            {
                //选中第一项
                var $tabLi = $(".tabMain li");
                var ntag = $tabLi.filter(":eq(" + ind + ")").attr("tags");
                //var ntag = $tabLi.first().attr("tags");
                $tabLi.attr("class", "tabMain_ul_li");
                $tabLi.filter("li[tags=" + ntag + "]").attr("class", "tabMain_ul_li_curr");
                $("#MRight>div").hide();
                $("#mainTab" + ntag).show();
                //所有标签关闭时，显示默认页
                if ($tabLi.length == 0) $("#mainTab-1").show();
            }
        }

        /*关闭当前页面*/
        function colsePage()
        {
            $("div.tabMain").find("li.tabMain_ul_li_curr a").click();
        }

        /*跟菜单目录添加图标*/
        function hacktree()
        {
            var tree = $(this),
                roots = tree.find('>li'),
                styles = 
                {
                    "系统功能模块维护": "set",
                    "基本功能": "gear",
                    "系统管理": "gear",
                    "内部投诉": "guard",
                    "微信公众号": "user",
                    "质培一体化": "coffee",
                    "人员管理": "users",
                    "指标监控": "chart",
                    "排班考勤": "time",
                    "公告管理": "msg",
                    "知识库": "know",
                    "系统设置": "set",
                    "业务办理助手": "card"
                };

            roots.addClass('menu');
            roots.find('>.hitarea').remove();
            roots.find('>span').each(function () 
            {
                $(this).prepend('<div class="menu-ico ' + (styles[$(this).text()] || styles['基本功能']) + '" ></div>');
            });
            //alerturl(); //修改内栏工单url
        }

        /*点击退出链接事件*/
        function onLoginOut()
        {
            //南京客服工号要进行工单提醒
            if ("1" == "12" && "1" == "1")
            {
                MsgWorder.closeShow(true);
            }
            else
            {
                LoginOut();
            }
        }

        /*退出系统*/
        function LoginOut()
        {
            $.get("Frame/LoginOut"); setTimeout(function () { window.location.href = "Frame/Login"; }, 300);
        }

        /*设置窗口大小*/
        function setWinSize()
        {
            var winHeight = $(window).height();
            var winWidth = $(window).width();
            var $MLeft = $("#MLeft");
            var $NInfo = $("#div_ntcinfo");
            gFrameWidth = winWidth - ($MLeft.is(":hidden") ? 0 : $MLeft.width()) - ($NInfo.is(":hidden") ? 0 : $NInfo.width()) - 7;
            $MLeft.height(winHeight - 37);
            $NInfo.height(winHeight - 65);
            $("#iframemsg").height(winHeight - 95);
            $(".mainTab>iframe").height(winHeight - 65).width(gFrameWidth);
        }

        /*左面板打开+关闭*/
        function leftCollapsed()
        {
            var winWidth = $(window).width() - 164;
            var $this = $(this);
            if ($this.attr("class") == "collapsed_yes")
            {
                $this.attr("class", "collapsed_no").css("margin-left", 0);
                $("#MLeft").hide();
                $("#MRight").css("margin-left", 0);
                $(".tabMain").css("left", "30px");
            }
            else
            {
                $this.attr("class", "collapsed_yes").css("margin-left", "172px");
                $("#MLeft").show();
                $("#MRight").css("margin-left", "174px");
                $(".tabMain").css("left", "200px");
            }
            setWinSize();
        }

        /*右面板打开+关闭*/
        function rightCollapsed()
        {
            var winWidth = $(window).width() - 164;
            var $this = $(this);
            if ($this.hasClass("collapsed_yes"))
            {
                $this.attr("class", "collapsed_no");
                $("#div_ntcinfo").show();
                $("#MRight").css("margin-right", "163px");
                //MsgNotice.set(30000);//回复定时刷新公告
            }
            else
            {
                $this.attr("class", "collapsed_yes");
                $("#div_ntcinfo").hide();
                $("#MRight").css("margin-right", "0px");
                //MsgNotice.clear();//隐藏公告栏时，停止定时刷新公告
            }
            setWinSize();
        }

        /*切换主页*/
        function SwitchIndex()
        {
            location.href = "Frame/Index3";
        }

        /*加载完成执行*/
        $(document).ready(function ()
        {
            /*--UI--*/
            $(".tabMain").on("click", "li>span", tabSelect).on("click", "li>a", tabClose); //页面选项卡选择和关闭事件
            $("#lSidebar_img").on("click", leftCollapsed); //左面板控制
            $("#rSidebar_img").on("click", rightCollapsed); //左面板控制
            setWinSize(); //设置窗口初始大小
            $(window).resize(setWinSize); //窗口大小变化
            /*--加载树--*/
            $("#resTree").bind('show', hacktree);
            $("#resTree").treeview({ url: "Frame/get_menu", unique: true });
            /*--打开默认页--*/
            goto(-1, '首页', 'Frame/UserMis');
            $(".tabMain ul li[tags='-1']>a").remove();
            /*--主页切换按钮显示--*/
            if ('2' == '3') $("#a_switch_index").show();
            /*--消息提醒--*/
            //MsgNotice.set(30000);//系统公告每隔30秒提醒一次
            //MsgTrain.set(10000);//案例库听音及在线培训登录后延迟10秒提醒一次
            //MsgWorder.set(60000);// 南京工单每隔1分钟预警一次
            //MsgRemedy.set1(600000);//每隔10分钟提醒员工和组织补救信息
            //MsgRemedy.set2(1800000);//每隔30分钟提醒质赔和班长补救信息
            //MsgInterOrder.set(1, 600000);//每个10分钟提醒一次内拦为按时回首的工单
            //MsgInterOrder.set(2, 3600000);//每个1小时提醒一次内拦为未完结的工单
            //MsgWorkflow.set();//工作流（内部工单）提醒
        });
    </script>

</body>
</html>