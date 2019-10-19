<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_order', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('uid')->default(0)->comment('用户ID')->index();
            $table->string('goods_name')->default('')->comment('商品名称');
            $table->string('goods_img')->default('')->comment('商品图片');
            $table->decimal('goods_price', 30, 2)->default(0.00)->comment('商品单价');
            $table->string('coin_type')->default('')->comment('币种类型');
            $table->decimal('ore_pool', 30, 2)->default(0.00)->comment('赠送的矿池数量');
            $table->unsignedTinyInteger('status')->default(1)->comment('1是购买成功 2是已发货');
            $table->string('to_name')->default('')->comment('收货人名称');
            $table->string('to_mobile')->default('')->comment('收货人手机');
            $table->string('to_address')->default('')->comment('收货人地址');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_order');
    }
}
