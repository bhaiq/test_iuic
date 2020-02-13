<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="telephone=no" name="format-detection">
    <meta content="telephone=no" name="format-detection">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>我的抽奖记录</title>
    <link rel="stylesheet" href="/script/lottery/css/style.css">
    <link rel="stylesheet" href="/script/lottery/css/swiper.min.css">
    <link rel="stylesheet" href="/script/lottery/css/jilu.css">
    <script src="/script/lottery/js/fontsize.js"></script>
</head>
<body>
<div class="head">
    <p>我的抽奖记录</p>
    <div class="box cl">
        <div class="fl" style="padding:0;margin:0;width:0.25rem;" tapmode="light" >
            <a href="/lottery/index?x-token={{ $x_token }}"><img src="/script/lottery/img/fh_an.png" alt="" class="img_f fl" ></a>
        </div>
    </div>
</div>
<div class="ul">
    <ul>
        @foreach($logs as $v)
            <li>{{ $v['created_at'] }} 获得 <span>{{ $v['goods_name'] }}</span></li>
        @endforeach
    </ul>
    @if($page != 'all')
    <p class="look" onclick="gogo()">查看更多>></p>
    @endif
</div>
</body>
<script src="/script/lottery/js/jquery-1.11.3.min.js"></script>
<script type="text/javascript">
    for (var i =0 ; i < $("li").length; i++) {
        if(i%2==0){
            $("li").eq(i).addClass("act")
        }
    }

    function gogo() {
        window.location.href = '/lottery/log?page=all&x-token=' + "{{$x_token}}";
    }
</script>
</html>