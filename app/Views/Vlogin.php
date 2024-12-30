<!-- v2.1.3.1.202412302005, from home -->

<!DOCTYPE html>
<html style="display: block;">
<head>
    <meta charset="utf-8">
    <title>运营管理系统</title>
    <link rel="stylesheet" type="text/css" href="assets/css/logincss2.css">
    <script src="assets/js/jquery.js"></script>
</head>

<body>
    <header class="header">
        <!--<h1 class="headerLogo"><a href="javascript:;" target="_blank" title=""><img src="./images/title.png" alt=""></a></h1>-->
    </header>
    <section class="main" id="mainBg" style="background-color: rgb(199,237,204);">
        <div class="main-inner" id="mainCnt" style="background-image: url(assets/images/logoin-bg.jpg);"></div>
        <!--登录框-->
        <div id="loginBlock" class="login tab-2">
            <div class="loginFunc">
                <!--
                <div id="lbApp" onClick="tabSlt(1,this)" class="loginFuncApp">管理工号登录</div>
                -->
                <div id="lbNormal" onClick="tabSlt(2,this)" class="loginFuncNormal"><b>用户登录</b></div>
            </div>
            <!-- 管理工号登录 -->
            <div id="appLoginTab" class="loginForm">
                <div id="login126">
                    <div id="idInputLine2" class="loginFormIpt showPlaceholder">
                        <select id="ddl_company" name="company_id" title="请输入部门名称" style="width:120px;">
                            <option value="" >请选择属地</option>
                            <option value="北京总公司" >北京总公司</option>
                            <option value="河北分公司" >河北分公司</option>
                            <option value="四川分公司" >四川分公司</option>
                        </select>

                        <!--<input class="formIpt2" tabindex="1" title="请输入帐号" id="idInput2" type="text" value="" autocomplete="off" style="width:120px;">-->
                    </div>
                    <div id="idInputLine" class="loginFormIpt showPlaceholder">
                        <b class="ico ico-uid"></b>
                        <input class="formIpt" tabindex="1" title="请输入帐号" id="idInput" type="text" value="" autocomplete="off">
                    </div>
                    <!-- 普通密码登录 -->
                    <div id="normalLogin">
                        <div id="pwdInputLine" class="loginFormIpt showPlaceholder">
                            <b class="ico ico-pwd"></b>
                            <input class="formIpt" tabindex="2" title="请输入密码" id="pwdInput" name="password" type="password">
                        </div>
                        <!--
                        <div id="div_checkcode" style="padding-bottom: 16px; margin-top:10px;display:none;">
                            <div style="clear:both; width:270px;">
                                <input type="text" id="tb_validcode" tabindex="3" style="width:75px;height:25px;margin: -20px 10px 0 12px;" />
                                <img id="valiCode" style="cursor: pointer; width:85px;height:31px;display:inline-block;" src="Common/ValidateImg" alt="验证码" onClick="ChgValidCode();" />
                                <a href="#" onClick="ChgValidCode();" style="float:right;margin-top:7px; margin-right:5px; font-size:x-small;">看不清楚</a>
                            </div>
                        </div>
                        -->
                        <div class="loginFormCheck">
                            <div id="lfAutoLogin" class="loginFormCheckInner">
                                <!--
                                <b class="ico ico-checkbox"></b>
                                <label id="remAutoLoginTxt" for="remAutoLogin">
                                    <input tabindex="3" title="十天内免登录" class="loginFormCbx" type="checkbox" id="remAutoLogin">
                                    十天内免登录
                                </label>
                                -->
                                <div id="whatAutologinTip">
                                    为了您的信息安全，请不要在网吧或公用电脑上使用此功能！
                                </div>
                            </div>
                            <div class="forgetPwdLine">
                                <!--
                                <a class="forgetPwd" href="javascript:;" target="_blank" title="找回密码">忘记密码了?</a>
                                -->
                            </div>
                        </div>
                        <div class="loginFormBtn">
                            <button id="btnLogin" class="btn btn-main btn-login" tabindex="6" type="submit">登&nbsp;&nbsp;录</button>
                            <a id="btnReset" class="btn btn-side btn-reg" href="javascript:;" target="_blank" tabindex="7">重&nbsp;&nbsp;置</a>
                        </div>
                    </div>
                    <!--
                    <div class="msgItem"></div>
                    <div style=" padding-left:20px; margin-top:30px;font-size: 12px;color: rgb(39,159,61);"><span>注：初始密码为身份证后6位</span></div>
                    -->
                </div>
            </div>
        </div>
    </section>

    <footer id="footer" class="footer">
        <!--<div class="footer-inner" id="footerInner">
            <a class="footerLogo" href="javascript:;" target="_blank"><img src="./images/logo.png" alt=""></a>
            <nav class="footerNav">
                <div><img src="./images/footerr.png" /></div>
            </nav>
        </div>-->
    </footer>
    <script type="text/javascript">
        /*全局变量*/
        var gSltTab = 2;
        // $("#idInputLine2").hide();
        /*Tab切换*/
        function tabSlt(tag, obj) {
            if (tag == 1) {
                $("#loginBlock").removeClass("login tab-2").addClass("login tab-1");
                $("#idInputLine").show();
                $("#idInputLine2").hide();
                gSltTab = 1
            }
            else {
                $("#loginBlock").removeClass("login tab-1").addClass("login tab-2");
                //$("#idInputLine").hide();
                $("#idInputLine2").show();
                gSltTab = 2
            }
        }
        /*显示提示信息*/
        var shander;
        function showTip(msg) {
            alert(msg);
            //clearTimeout(shander);
            //$(".msgItem").text(msg);
            //shander = setTimeout(function () { $(".msgItem").fadeIn(1000).delay(2000).text(""); }, 5000);
        }
        /*获取url参数*/
        function getQueryString(name) {
            var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
            var r = window.location.search.substr(1).match(reg);
            if (r != null) return unescape(r[2]); return null;
        }
        /*加载完成*/
        $(document).ready(function () {
            if ('0' == "1") $("#div_checkcode").show();
            $("#ddl_company").val('1');
            /*重置*/
            $(document).on('keydown', function (e) { e.keyCode == 13 && $("#btnLogin").click(); });
            $("#btnReset").click(function () { $("#idInput").val(""); $("#ddl_company").val(""); $("#pwdInput").val(""); });
            $("#btnLogin").click(function () {
                var $lgBtn = $(this);
                $lgBtn.attr("disabled", "disabled");
                var parms = {
                    company_id: gSltTab == 1 ? "北京总公司" : $("#ddl_company").val(),

                    userid: $.trim($("#idInput").val()),
                    userpwd: $.trim($("#pwdInput").val()),
                    pub_login: '0',
                    valid_code: $.trim($("#tb_validcode").val())
                };

                if (parms.userid == "" || parms.userpwd == "" || ('0' == "1" && parms.valid_code == "")) {
                    showTip("请填写完整信息!");
                    $lgBtn.removeAttr("disabled");
                }
                else {
                    //showTip("");
                    $.ajax({
                        type: "POST",
                        url: "<?php echo $NextPage; ?>",
                        data: parms,
                        cache: false,
                        success: function (r) {
                            if (("" + r).indexOf("pub") >= 0) {
                                showTip("密码错误，剩余尝试次数" + r.split("pub")[1] + "!");
                                $lgBtn.removeAttr("disabled");
                                return;
                            }
                            else if (r == "1") {
                                var fromurl = getQueryString("from");
                                window.location.href = gSltTab == 1 ? "Frame/Index" : "Frame/Index";
                            }
                            else {
                                console.log(r);
                                switch (r) {
                                    case "-2": showTip("密码输入错误5次，工号已锁定！"); break;
                                    case "-1": showTip("你的工号没有公网登录权限！"); break;
                                    case "0": showTip("验证码错误！"); break;
                                    case "2": showTip("用户不存在！"); break;
                                    case "3": showTip("密码不正确！r="+r); break;
                                    case "4": showTip("账号被锁定，请与管理员联系！"); break;
                                    case "10": showTip("属地错误！"); break;
                                    default: showTip("异常错误！r="+r); break;
                                }
                                $lgBtn.removeAttr("disabled");
                            }
                        }
                    });
                }
            });
        });

        function ChgValidCode() {
            document.getElementById("valiCode").src = "Common/ValidateImg?" + Math.random();
        }
    </script>
</body>
</html>