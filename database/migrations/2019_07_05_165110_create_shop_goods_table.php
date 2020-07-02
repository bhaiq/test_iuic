<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopGoodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_goods', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('goods_name')->default('')->comment('商品名称');
            $table->string('goods_img')->default('')->comment('商品图片');
            $table->string('goods_info')->default('')->comment('商品简介');
            $table->decimal('goods_price', 30, 2)->default(0.00)->comment('商品单价');
            $table->string('coin_type')->default('')->comment('币种类型');
            $table->decimal('ore_pool', 30, 2)->default(0.00)->comment('赠送的矿池数量');
            $table->unsignedTinyInteger('buy_count')->default(0)->comment('商品价值数量，用来累积升级');
            $table->text('goods_details')->nullable()->comment('商品详情');
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
        Schema::dropIfExists('shop_goods');
    }
}
