<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="telephone=no" name="format-detection">
    <meta content="telephone=no" name="format-detection">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>幸运大转盘</title>
    <link rel="stylesheet" href="/script/lottery/css/style.css">
    <link rel="stylesheet" href="/script/lottery/css/swiper.min.css">
    <link rel="stylesheet" href="/script/lottery/css/index.css">
    <script src="/script/lottery/js/fontsize.js"></script>
</head>
<body>
<div class="head">
    <p>幸运大转盘</p>
    <div class="box cl">
        <div class="fl" style="padding:0;margin:0;width:0.25rem;" tapmode="light" >
            <img src="/script/lottery/img/fh_an.png" alt="" class="img_f fl" >
        </div>
    </div>
</div>
<div class="lp_box">
    <div class="tit">
        <img src="/script/lottery/img/zp_dbt.png" class="ti_img">
    </div>
    <div class="zj">
        <img src="/script/lottery/img/zp_jl_icon.png" class="jl" onclick="jilu()">

        <div class="swiper">
            <div class="swiper-container">
                <div class="swiper-wrapper">
                    @foreach($logs as $v)
                        <p>恭喜{{ $v['user']['nickname'] }}抽中{{ $v['goods']['name'] }}</p>
                    @endforeach
                </div>
                <!-- Add Pagination -->
                <div class="swiper-pagination"></div>
            </div>
        </div>

        <img src="/script/lottery/img/zp_gz_icon.png" class="gz" onclick="guize()">
    </div>
    <div class="lun_box">

        <div class="lun  lun1">

            @foreach($goods as $k => $v)

                <div class="img1 wz{{ ($k+1) }}">
                    <img src="{{ $v['img'] }}" class="jl_iocn">
                </div>

            @endforeach

        </div>

        <img src="/script/lottery/img/zp_btn.png" class="btn" >

    </div>
    <div class="btn_box">
        <div id="one_click" class="one" onclick="payone(1)">
            <p class="tet">抽一次</p>
            <p class="num">{{ bcmul($one_num, 1, 4) }} IUIC</p>
        </div>
        <div id="ten_click" class="ten" onclick="payone(10)">
            <p class="tet">抽十次</p>
            <p class="num">{{ bcmul($one_num, 10, 4) }} IUIC</p>
        </div>
    </div>
    <div class="tongji">
        <p>今日已抽次数：<span id="lottery_count">{{ $lottery_count }}</span></p>
        <p>可用IUIC：<span id="wallet_num">{{ $wallet_num }}</span></p>
    </div>

    <div class="list">
        <div class="record_title">
            <p class="t">奖池实时记录</p>
        </div>
        <div class="record_list">

            @foreach($logs as $v)
                <p>恭喜{{ $v['user']['nickname'] }}抽中{{ $v['goods']['name'] }}</p>
            @endforeach

        </div>
    </div>
</div>
<div class="bg meng">
    <div class="tet_box">
        <img src="/script/lottery/img/tc_bt.png" class="ti_im">
        <div class="m_box">
            <div class="zhongyi">
                <img src="/script/lottery/img/xxcy_120px.png" class="shop_img">
                <p class="zj_tet">温馨提示~</br><span id="error_msg">没有信息哦！</span></p>
            </div>
        </div>
        <div class="bt_box">
            <p class="clo" onclick="clo()">关闭弹窗</p>
            {{--<p class="zai" onclick="cone()">再来一次</p>--}}
        </div>
    </div>
</div>

<div class="bg meng1">
    <div class="tet_box">
        <img src="/script/lottery/img/tc_bt.png" class="ti_im">
        <div class="m_box">
            <div class="zhongyi">
                <img id="one_return_img" src="/script/lottery/img/xxcy_120px.png" class="shop_img">
                <p class="zj_tet" >恭喜您获得 <span id="zhong">超级幸运奖</span>*1	 </br>再接再厉哦！</p>
            </div>
        </div>
        <div class="bt_box">
            <p class="clo" onclick="cloo()">关闭弹窗</p>
            {{--<p class="zai" onclick="cone()">再来一次</p>--}}
        </div>
    </div>
</div>

<div class="bg meng2">
    <div class="tet_box">
        <img src="/script/lottery/img/tc_bt.png" class="ti_im">
        <div class="m_box">
            <div class="zhongten" id="zhongten">
            </div>
            <p class="zj_tet zz" >恭喜您获得 <span id="zhongt">超级幸运奖*1 超级幸运奖*1 超级幸运奖*1 超级幸运奖*1</span>	 </br>再接再厉哦！</p>
        </div>
        <div class="bt_box">
            <p class="clo" onclick="clooo()">关闭弹窗</p>
            {{--<p class="zai" onclick="cten()">再来一次</p>--}}
        </div>
    </div>
</div>
<div class="pass_box bg">
    <div class="p_box">
        <p class="tit_msg">请输入交易密码</p>
        <p class="ipt_box"> <input type="password" name="paypass" class="ipt"></p>
        <div class="p_btn_box">
            <p class="p_btn_left" onclick="payclo()">取消</p>
            <p class="p_btn_right" onclick="payok()">确认</p>
        </div>
    </div>
</div>
</body>
<script src="/script/lottery/js/jquery-1.11.3.min.js"></script>
<script src="/script/lottery/js/jquery.rotate.min.js"></script>
<script src="/script/lottery/js/recoreRoll.js"></script>
<script src="/script/lottery/js/swiper.min.js"></script>
<script>
    for (var i =0 ; i < $(".record_list p").length; i++) {
        if(i%2==0){
            $(".record_list p").eq(i).addClass("act")
        }
    }
    $(".wz2").rotate(45)
    $(".wz3").rotate(90)
    $(".wz4").rotate(135)
    $(".wz5").rotate(180)
    $(".wz6").rotate(225)
    $(".wz7").rotate(270)
    $(".wz8").rotate(315)
    var mySwiper = new Swiper('.swiper-container', {
        direction: 'vertical',
        slidesPerView: 1, // 每页显示几个slide
        spaceBetween: 0, // slide的间距px
        followFinger : false, //
        speed: 400, // 速度
        loop: true, // 循环
        autoplay: {
            delay: 1000,
            stopOnLastSlide: false,
            disableOnInteraction: true,
        },
    });
    var bRotate=false;

    //密码框取消按钮事件
    function payclo(){
        $(".pass_box").css("display","none");
        toggleBody(0);
    }
    //抽奖弹出密码框事件
    function payone(num){
        toggleBody(1);
        $(".pass_box").css("display","block");
        type=num;
        $('input[name=paypass]').val('');
    }
    //密码框输入确认事件
    function payok(){
        payclo();
        console.log(type);
        if (type==1) {
            cone()
        }else if (type==10) {
            cten()
        }
    }

    // 限制点击
    function xzClick() {
        $('#one_click').attr('onclick', '');
        $('#ten_click').attr('onclick', '');
    }

    // 开放点击
    function kfClick() {
        $('#one_click').attr('onclick', "payone(1)");
        $('#ten_click').attr('onclick', "payone(10)");
    }

    // 错误信息提示
    function error_tx(txt) {
        $(".meng").css("display","block")
        $('#error_msg').text(txt);
    }

    //抽奖一次
    function cone(){

        clo()
        cloo()
        clooo()

        xzClick();

        $.post(
            '/lottery/submit',
            {
                'count' : 1,
                'x-token' : "{{ $x_token }}",
                '_token': "{{ csrf_token() }}",
                'paypass': $('input[name=paypass]').val(),
            },
            function (d) {
                if(d.code == 1){

                    // 成功的情况下用户抽奖次数增加，余额减少
                    $('#lottery_count').html(parseInt($('#lottery_count').html())+1);
                    $('#wallet_num').html($('#wallet_num').html() - "{{ $one_num }}");

                    rotateFn(d.data[0].ds, d.data[0].goods_name, d.data[0].goods_img);
                }else{
                    kfClick();
                    error_tx(d.msg);
                }

            }
        );

    }
    //抽奖十次
    function cten(){

        clo();
        cloo();
        clooo();

        // 先清空图片DIV
        $('#zhongten').html(' ');

        // 清空说明span
        $('#zhongt').html(' ');

        xzClick();

        $.post(
            '/lottery/submit',
            {
                'count' : 10,
                'x-token' : "{{ $x_token }}",
                '_token': "{{ csrf_token() }}",
                'paypass': $('input[name=paypass]').val(),
            },
            function (d) {
                if(d.code == 1){

                    // 成功的情况下用户抽奖次数增加，余额减少
                    $('#lottery_count').html(parseInt($('#lottery_count').html())+10);
                    $('#wallet_num').html($('#wallet_num').html() - "{{ $one_num*10 }}");

                    /*$.each(d.data, function (kk, vv) {
                        rotateFnn(kk.ds, kk.goods_name);
                    });*/

                    var timesRun = 0;
                    var interval = setInterval(function(){

                        timesRun += 1;
                        if(timesRun === 10){
                            clearInterval(interval);
                            kfClick();
                        }

                        rotateFnn(d.data[timesRun-1].ds, d.data[timesRun-1].goods_name, d.data[timesRun-1].goods_img);

                    }, 2000);

                }else{
                    kfClick();
                    error_tx(d.msg);
                }

            }
        );
    }
    function rnd(n,m){
        var num=Math.floor(Math.floor(Math.random()*(m-n+1)+n))
        return num;
    }
    function rotateFn(angles,txt, img){  //控制轮盘在angle度停下
        bRotate=!bRotate;
        $('.lun1').stopRotate();
        $(".lun1").rotate({
            angle:0,                //旋转的角度
            animateTo:angles+1800,  //从当前角度旋转多少度
            duration:3000,          //持续时间
            callback:function(){    //回调函数

                $(".meng1").css("display","block")
                $("#zhong").text(txt)
                $("#one_return_img").prop('src', img);

                toggleBody(1)  //在跳出弹窗的时候
                bRotate=!bRotate;
            }
        });
        kfClick();
    }

    function rotateFnn(angles,txt, img){  //控制轮盘在angle度停下

        $("#zhongt").append(txt + '*1 ');
        $('#zhongten').append('<img src="' + img + '" class="shop_img_ten">');

        bRotate=!bRotate;
        $('.lun1').stopRotate();
        $(".lun1").rotate({
            angle:0,                //旋转的角度
            animateTo:angles+1800,  //从当前角度旋转多少度
            duration:3000,          //持续时间
            callback:function(){    //回调函数
                // alert(txt);
                // tt +="   "+txt
                // console.log()
                $(".meng2").css("display","block");

                toggleBody(1)  //在跳出弹窗的时候
                bRotate=!bRotate;
            }
        });
    }

    // function start(){
    // 	var angle=0
    // 	var time=setInterval(ro,50)
    // 	function ro(){
    // 	if (angle==100) {
    // 		clearInterval(time)
    // 	}else{
    // 		angle+=9
    // 		$(".lun1").rotate(angle)
    // 	}
    // }
    // }
    $(document.body).ready(function(){
        $(".record_list").RollTitle({line:1,speed:800,timespan:1});
    });

    $('.swap').html($('.news_li').html());
    x = $('.news_li');
    y = $('.swap');
    h = $('.news_li li').length * 20; //20为每个li的高度
    var hh = $('.news_li li').length;
    if (hh > 1)
    //setTimeout(b,3000);//滚动间隔时间 现在是3秒
        b();
    b();

    function b() {
        t = parseInt(x.css('top'));
        //alert(t)
        y.css('top', '20px');
        x.animate({
            top: t - 20 + 'px'
        }, 'slow'); //20为每个li的高度
        if (Math.abs(t) == h - 20) { //20为每个li的高度
            y.animate({
                top: '0px'
            }, 'slow');
            z = x;
            x = y;
            y = z;
        }
        setTimeout(b, 3000); //滚动间隔时间 现在是3秒
    }




    function toggleBody(isPin){

        if(isPin){

            document.body.style.height = '100vh'

            document.body.style['overflow-y'] = 'hidden'
        }

        else{

            document.body.style.height = 'unset'

            document.body.style['overflow-y'] = 'auto'

        }
    }


    function clo(){
        $(".meng").css("display","none")
        toggleBody(0)
    }
    function cloo(){
        $(".meng1").css("display","none")
        toggleBody(0)
    }
    function clooo(){
        $(".meng2").css("display","none")
        toggleBody(0)
    }

    function guize(){
        window.location.href = "/lottery/info?x-token={{$x_token}}";
    }
    function jilu(){
        window.location.href = "/lottery/log?x-token={{$x_token}}";
    }
</script>
</html>