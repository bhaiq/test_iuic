<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="/script/down/css/global.css">
    <title>下载</title>
    <style>
        .wxtip {
            display: none;
            position: absolute;
            width: 100%;
            height: 100%;
            background: #cecece;
            opacity: 0.6;
        }

        .wxtipimg {
            display: none;
            position: absolute;
            right: 0;
            top: 0;
            width: 60vw;
            z-index: 2;
        }
    </style>
</head>
<body>
<div class="reform">
    <div class="top-header">
        <div class="logo"><img src="/script/down/images/logo.png"><br>中链交易所</div>
        <p>下载中链交易所 - 和好友一起分享矿池</p>
        <div class="btn"><span>点击下方按钮下载</span></div>
    </div>
    <div class="center">
        <ul>
            <li style="text-align: center;" onclick="down2()"><img src="/script/down/images/az.png"><span>Android版本下载</span></li>
            <li style="text-align: center;" onclick="down1()"><img src="/script/down/images/pg.png"><span>ios版本下载</span></li>
        </ul>
    </div>
</div>
<div class="wxtip"></div>
<img class="wxtipimg" src="/script/down/images/tip.png">
<script type="text/javascript">
    function down1() {

        // alert('暂时不开放');

        if (is_weixn()) {
            document.getElementsByClassName("wxtip")[0].style.display = "block";
            document.getElementsByClassName("wxtipimg")[0].style.display = "block";
            return false;
        }

        window.open("{{$ios_url}}");
    }

    function down2() {
        if (is_weixn()) {
            document.getElementsByClassName("wxtip")[0].style.display = "block";
            document.getElementsByClassName("wxtipimg")[0].style.display = "block";
            return false;
        }else{

            window.open("{{$and_url}}");
        }

    }

    function is_weixn() {
        var ua = navigator.userAgent.toLowerCase();
        if (ua.match(/MicroMessenger/i) == "micromessenger") {
            return true;
        } else {
            return false;
        }
    }
</script>
</body>
</html>