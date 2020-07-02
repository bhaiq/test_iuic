<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOtcOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('otc_order', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedbigInteger('sell_id')->default(0)->index();
            $table->unsignedbigInteger('buy_id')->default(0)->index();
            $table->decimal('amount', 30, 8)->comment('交易数量');
            $table->decimal('price', 30, 4)->comment('单价');
            $table->decimal('total_price', 30, 4)->comment('总价');
            $table->unsignedtinyInteger('status')->default(0)->comment('状态 0 进行中 1 已完成 2 取消');
            $table->boolean('is_pay')->default(false)->comment('是否已支付');
            $table->unsignedtinyInteger('is_pay_coin')->default(0)->comment('是否已发币');
            $table->unsignedbigInteger('appeal_uid')->default(0)->comment('申诉人id');
            $table->unsignedbigInteger('uid')->default(0)->comment('下单人id');
            $table->unsignedbigInteger('seller_id')->default(0)->comment('出售人');
            $table->unsignedbigInteger('buyer_id')->default(0)->comment('购买人');
            $table->unsignedtinyInteger('type')->default(0)->comment('交易类型 0出售,1购买');
            $table->unsignedtinyInteger('coin_id')->default(0)->comment('交易币ID');
            $table->string('expansion')->default('');
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
        Schema::dropIfExists('otc_order');
    }
}
