<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Down Page</title>
    <style>
        html{
            width: 100%;
            min-height: 100%;
            background: url(./assets/xz_bj.png);
            background-size:100% 68.215%;
            background-repeat: no-repeat;
        }
        body {
            margin: 0;
        }
        .title {
            margin-top: 8.669vh;
            margin-left: 14.4vw;
        }
        .logo {
            margin-top: 12.593vh;
            margin-left: 37.6vw;
        }
        .logo-title {
            margin-top: 2.248vh;
            margin-left: 33.86vw;
        }
        .android-down {
             background-image: url('./assets/xz_an.png');
             width: 82.93vw;
             height: 7.496vh;
             background-size:100% 100%;
             margin-top: 13.94vh;
             margin-left: 8.53vw;
            display: flex;
            justify-content: center;
            align-items: center;
         }

        .ios-down {
            background-image: url('./assets/xz_an.png');
            width: 82.93vw;
            height: 7.496vh;
            background-size:100% 100%;
            margin-top: 4.047vh;
            margin-left: 8.53vw;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .ios-down img {
            width: 17pt;
        }

        .android-down img {
            width: 17pt;
        }

        .text {
            font-size: 15pt;
        }
    </style>
    <script type="text/javascript" src="../script/jquery-1.11.3.min.js"></script>
</head>
<body>
    <div class="title">
        <img style="width: 71.2vw;" src="./assets/xz_bt.png" alt="">
    </div>
    <div class="logo">
        <img style="width: 24.8vw;" src="./assets/xz_logo.png" alt="">
    </div>
    <div class="logo-title">
        <img style="width: 32.53vw;" src="./assets/xz_hulk.png" alt="">
    </div>
    <div class="android-down" onclick="jump(0)">
        <img style="margin-right: 20pt" src="./assets/android.png"  />
        <span class="text">Android 版本下载</span>
    </div>
    <div class="ios-down" onclick="jump(1)">
        <img style="margin-right: 20pt" src="./assets/ios.png" />
        <span class="text">Ios 版本下载</span>
    </div>
</body>
<script>
  function jump(type) {
    if (!this.is_weixn()) {
      $.get('{{url('api/system/checkVersion')}}?type=' + type + '&version=0.0.0', function(data) {
        location.href = data.url;
      });
    }
  }

  function is_weixn() {
    const ua = navigator.userAgent.toLowerCase();
    if (ua.match(/MicroMessenger/i) == 'micromessenger') {
      alert('请在浏览器内打开该网页');
      return true;
    } else {
      return false;
    }
  }
</script>
</html>
