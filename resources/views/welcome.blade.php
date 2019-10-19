<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/register.css">
    <script src="../script/fontsize.js"></script>
</head>

<body>
<div id="app" >
    <div class="header cl">
        <p class="fr type" onclick="registerSwitch(1)">邮箱注册</p>
    </div>
    <div class="tet">
        手机注册
    </div>
    <form method="post" action="{{url('api/user')}}">
        <input type="hidden" name="type" value="1">
        <div class="ipt_box cl">
            <p class="diqu fl" >+86</p>
            <input type="text" class="user fl" name="username" placeholder="请输入手机号" >
        </div>
        <div class="ipt_box ipt_code cl">
            <input type="text" class="code" placeholder="请输入验证码">
            <span class="yzm fr" id="yzm" name="code" onclick="shoujihuoquyanzhengma()">获取验证码</span>
        </div>

        <div class="ipt_box">
            <input type="password" class="user" placeholder="请输入登录密码">
            <input type="text" class="user" name="password" style="display:none;" placeholder="请输入登录密码">
        </div>

        <div class="ipt_box">
            <input type="password" class="user" name="re_password" placeholder="请确认登录密码" >
        </div>

        <div class="ipt_box">
            <input type="text" v-model="invite" name="invite_code" class="user" placeholder="请输入您的邀请码" >
        </div>

        <div class="btn" tapmode="light" onsubmit="">
            确定
        </div>
    </form>

    <div class="log" tapmode="light" onclick="jumpDownView()">
        已有账号? 去登录
    </div>
</div>
</body>
<script type="text/javascript" src="../script/jquery-1.11.3.min.js"></script>
<script type="text/javascript" src="../script/vue.min.js"></script>
<script type="text/javascript">

  var appdata=new Vue({
    el:"#app",
    data:{
      user:"",
      password:"",
      paypassword:"",
      invite:"MiV76B",
      code:"",
      nickname:"",
      fs:"1"
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

  function registerSwitch(type) {
    if(type == 1) {
      $('.tet').text('邮箱注册');
      $('.type').attr('onclick', 'registerSwitch(0)');
      $('.type').text('手机注册');
      $('.diqu').hidden();
    }else{
      $('.tet').text('手机注册');
      $('.type').attr('onclick', 'registerSwitch(1)');
      $('.type').text('邮箱注册');
    }
  }

  function getCode() {

  }

  function daojishi(){
    appdata.fs=0;
    var clock = '';
    nums = 60;
    var btn=document.getElementById("yzm");
    btn.innerHTML=nums+'s';
    clock = setInterval(function(){
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
    window.location.href = '{{url('down')}}';
  }
</script>
</html>
