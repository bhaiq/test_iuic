<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/register.css">
    <script src="script/fontsize.js"></script>
</head>

<body>
<div id="app" >
    <div class="header cl">
        <p class="fr type">邮箱注册</p>
    </div>
    <div class="tet">
        注册账号
    </div>

    <div class="ipt_box cl">
        <p class="diqu fl">+86</p>
        <input type="text" class="user fl" v-bind:placeholder=@{{lang}} oninput="ipt(this)">
{{--        <img src="image/close.png" alt="" class="close"  tapmode="light" >--}}
    </div>
    <div class="ipt_box ipt_code cl">
        <input type="text" class="code" placeholder="请输入验证码">
        <span class="yzm fr" id="yzm" tapmode="light" onclick="shoujihuoquyanzhengma()">获取验证码</span>
    </div>

    <div class="ipt_box">
        <input type="password" class="user" placeholder="请输入登录密码">
        <input type="text" class="user" style="display:none;" placeholder="请输入登录密码">
    </div>

    <div class="ipt_box">
        <input type="password" class="user" placeholder="请确认登录密码" >
    </div>

    <div class="ipt_box">
        <input type="text" v-model="invite" class="user" placeholder="请输入您的邀请码" >
    </div>

    <div class="btn"  tapmode="light">
        确定
    </div>

    <div class="log" tapmode="light" click="">
        已有账号? 去登录
    </div>
</div>
</body>
<script type="text/javascript" src="script/api.js"></script>
<script type="text/javascript" src="script/vue.min.js"></script>
<script type="text/javascript" src="script/reset.js"></script>
<script type="text/javascript">
  var appdata=new Vue({
    el:"#app",
    data:{
      lang: {
        cn: {
          user: '请输入手机号',
          password: '请输入密码',
          paypassword: '请确认登录密码',
          code: '请输入验证码'
        },
        en: {
          user: '请输入手机号',
          password: '请输入密码',
          paypassword: '请确认登录密码',
          code: '请输入验证码'
        }
      },
      onlang: {
        user: '请输入手机号',
        password: '请输入密码',
        paypassword: '请确认登录密码',
        code: '请输入验证码'
      },
      user:"",
      password:"",
      paypassword:"",
      invite:"MiV76B",
      code:"",
    },
    methods:{

    }
  })


  function shoujihuoquyanzhengma(){
    if(appdata.fs==0){
      return;
    }
    daojishi()
  }

  function daojishi(){
    appdata.fs=0
    var clock = '';
    nums = 60;
    var btn=document.getElementById("yzm");
    btn.innerHTML=nums+'s'
    clock=setInterval(function(){
      nums--;
      if(nums > 0){
        btn.innerHTML=nums+'s'
      }else{
        appdata.fs=1
        clearInterval(clock); //清除js定时器
        btn.innerHTML='重新获取'
      }
    }, 1000); //一秒执行一次
  }

  function jumpDownView() {

  }
</script>
</html>
