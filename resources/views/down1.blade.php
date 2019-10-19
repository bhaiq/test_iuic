<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Down Page</title>
    <style>
        @import url('./assets/style.css');
        @import url('./assets/index.css');

        html{
            width: 100%;
            min-height: 100%;
            background: url(./assets/xz_bj.png);
            background-size:100% 70%;
            background-repeat: no-repeat;
        }
        #app {
            height: 100%;
        }
    </style>
    <script src="../script/fontsize.js"></script>
    <script type="text/javascript" src="../script/jquery-1.11.3.min.js"></script>
  </head>
  <body>
        <div >

        </div>
          <div class="down_img" style="margin-top: 300px;">
              <img style="width: 30%; border-radius: 30px;" src="./assets/down.png"/>
          </div>
          <div class="title" style="margin-top: 10px;">
              <p class="name">Hulk<span>.vip</span></p>
          </div>


          <div class="android" style="background-image: url('./assets/xz_an.png'); border-radius: 5px; border-color: #ffffff;" onclick="jump(0)">
              <div class="box cl">
                  <img src="./assets/android.png" class="fl" />
                  <p class="fl">Android 版本下载</p>
              </div>
          </div>

          <div class="ios" style="background-image: url('./assets/xz_an.png'); border-radius: 5px; border-color: #ffffff;" onclick="jump(1)">
              <div class="box cl" >
                  <img src="./assets/ios.png" class="fl" />
                  <p class="fl">Ios 版本下载</p>
              </div>
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
