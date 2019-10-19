<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title></title>
    <meta name="viewport"
          content="maximum-scale=1.0, minimum-scale=1.0, user-scalable=0, initial-scale=1.0, width=device-width"/>
    <meta name="format-detection" content="telephone=no, email=no, date=no, address=no">
    <link rel="stylesheet" type="text/css" href="/script/layui/css/layui.css"/>
    <link rel="stylesheet" type="text/css" href="/script/css/style.css"/>
</head>
<body>
<div class="header">
    <h2>手机注册</h2>
</div>
<div class="form">
    <p style="padding: 0px;">
        <em>+86</em><input style="padding-left: 15px;" type="text" name="user" placeholder="请输入手机号码"/>
    </p>
    <p>
        <input type="text" name="captcha" placeholder="请输入验证码"/>
        <a href="javascript:void(0);" id="captcha" onclick="gettwoma()" >获取验证码</a>
    </p>
    <p>
        <input type="password" name="pass" placeholder="请输入登陆密码"/>
    </p>
    <p>
        <input type="password" name="repass" placeholder="请确认登陆密码"/>
    </p>
    <p>
        <input type="text" name="code" value="{{$invite_code}}" <?php echo !empty($invite_code) ? 'readonly' : ''; ?> placeholder="请输入邀请码"/>
    </p>
</div>

<div class="button">
    <button id="sub"> 确定</button>
</div>

<div class="footer" id="login">
    已有账号？去下载
</div>

<script src="/script/layui/layui.js"></script>
<script src="/script/js/jquery-1.8.3.min.js"></script>
<script>
    layui.use('layer', function(){ //独立版的layer无需执行这一句
        var $ = layui.jquery, layer = layui.layer; //独立版的layer无需执行这一句

        $('#sub').on('click', function(){
            var user = $('input[name=user]').val();
            var captcha = $('input[name=captcha]').val();
            var pass = $('input[name=pass]').val();
            var repass = $('input[name=repass]').val();
            var code = $('input[name=code]').val();


            $.ajax({
                url:"/api/user",    //请求的url地址
                dataType:"json",   //返回格式为json
                data:{'username':user,'code':captcha,'password':pass,'re_password':repass,'invite_code':code,'type':1},
                type:"POST",   //请求方式
                success:function(d){
                    alert('注册成功');
                    window.location.href = '/register/{{$invite_code}}';
                },
                error:function(d){
                    var json = eval('(' + d.responseText + ')');
                    layer.alert(json.detail);
                }
            });

        });

        $('#login').on('click', function(){
            window.location.href = "/download";
        });

    });

    // 倒计时
    function daojishi(){
        var clock = '';
        nums = 60;
        var btn=document.getElementById("captcha");
        $(btn).attr("onclick","")
        $(btn).text(nums+'秒')
        setInterval(function(){
            nums--;
            if(nums > 0){
                $(btn).text(nums+'秒')
            }else{
                clearInterval(clock); //清除js定时器
                $(btn).attr("onclick","gettwoma()")
                $(btn).text("重新获取")
            }
        }, 1000); //一秒执行一次
    }

    // 获取验证码
    function gettwoma(){

        var username = $('input[name=user]').val();

        $.ajax({
            url:"/api/userCode?username=" + username + "&type=1",    //请求的url地址
            dataType:"json",   //返回格式为json
            type:"GET",   //请求方式
            success:function(d){
                layer.alert('发送成功');
                daojishi();
            },
            error:function(d){
                var json = eval('(' + d.responseText + ')');
                layer.alert(json.detail);
            }
        });

    }

</script>
</body>
</html>
