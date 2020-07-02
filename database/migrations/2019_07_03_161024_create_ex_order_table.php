<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ex_order', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('uid')->default(0)->comment('用户ID');
            $table->unsignedBigInteger('team_id')->default(0)->comment('交易对ID');
            $table->decimal('price', 30, 8)->default(0.00)->comment('单价');
            $table->decimal('amount', 30, 8)->default(0.00)->comment('挂单商品币数量');
            $table->decimal('amount_lost', 30, 8)->default(0.00)->comment('剩余商品币数量');
            $table->decimal('amount_deal', 30, 8)->default(0.00)->comment('花费法币数量');
            $table->unsignedTinyInteger('type')->default(0)->comment('交易类型：0 卖，1 买');
            $table->unsignedTinyInteger('status')->default(0)->comment('状态：0 未完成，1 已完成，2取消');
            $table->timestamps();
        });

        Schema::create('ex_order_together', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('team_id')->default(0)->comment('交易对ID');
            $table->unsignedBigInteger('sell_id')->default(0)->comment('售卖表ID');
            $table->unsignedBigInteger('buy_id')->default(0)->comment('购买表ID');
            $table->unsignedBigInteger('seller_id')->default(0)->comment('售卖人ID');
            $table->unsignedBigInteger('buyer_id')->default(0)->comment('购买人ID');
            $table->decimal('pirce', 30, 8)->default(0.00)->comment('成交价格');
            $table->unsignedTinyInteger('type')->default(0)->comment('1买单匹配 0卖单匹配');
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
        Schema::dropIfExists('ex_order');
        Schema::dropIfExists('ex_order_together');
    }
}
