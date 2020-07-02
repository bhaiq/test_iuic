<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="telephone=no" name="format-detection">
    <meta content="telephone=no" name="format-detection">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>幸运大转盘规则</title>
    <link rel="stylesheet" href="/script/lottery/css/style.css">
    <link rel="stylesheet" href="/script/lottery/css/swiper.min.css">
    <link rel="stylesheet" href="/script/lottery/css/guize.css">
    <script src="/script/lottery/js/fontsize.js"></script>
</head>
<body>
{{--<div class="head">
    <p>规则</p>
    <div class="box cl">
        <div class="fl" style="padding:0;margin:0;width:0.25rem;" tapmode="light" >
            <a href="/lottery/index?x-token={{ $x_token }}"><img src="/script/lottery/img/fh_an.png" alt="" class="img_f fl" ></a>
        </div>
    </div>
</div>--}}

<div class="tet_box">
    <p class="title">幸运大转盘游戏规则</p>
    <p class="content">
        抽奖一次需消耗 <span class="span">{{ $one_num }} IUIC</span>，
        十连抽需要消耗<span class="span">{{ $one_num*10 }} IUIC</span>。
        且中奖机会会大大提升！实际情况以抽奖结果为准，感谢您的参与！
    </p>
    <p class="msg"><img src="/script/lottery/img/jp_bt.png" class="img"/></p>

    @foreach($goods as $k => $v)

        <div class="list">
            <div class="item">
                <p class="titl">
                    {{$v['name']}}
                </p>
                <div class="con_box">
                    <img src="{{$v['img']}}" alt=""  class="coin"/>
                    <div class="jp_msg">{{$v['info']}}</div>
                </div>
            </div>
        </div>

    @endforeach

</div>

<p class="msg"> 游戏最终解释权归IUIC所有</p>
</body>
</html>