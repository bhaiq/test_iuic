<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOtcPublishSellTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('otc_publish_sell', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('uid')->default(0)->index();
            $table->decimal('amount', 30, 8)->default(0.00)->comment('总购买数');
            $table->decimal('amount_min', 30, 8)->default(0.00)->comment('最小购买数');
            $table->decimal('amount_max', 30, 8)->default(0.00)->comment('最大购买数');
            $table->decimal('amount_lost', 30, 8)->default(0.00)->comment('剩余部分');
            $table->decimal('price', 20, 3)->default(0.00)->comment('单价');
            $table->boolean('pay_wechat')->default(false)->comment('微信支付');
            $table->boolean('pay_alipay')->default(false)->comment('支付宝');
            $table->boolean('pay_bank')->default(false)->comment('银行卡');
            $table->unsignedtinyInteger('coin_id')->default(0)->comment('币种ID')->index();
            $table->unsignedtinyInteger('is_over')->default(0)->comment('是否已完成 0 未完成 1 已完成 2 已取消')->index();
            $table->unsignedtinyInteger('currency')->default(0)->comment('币种 0 人民币 1 美元')->index();
            $table->string('remark')->default('')->comment('备注');
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
        Schema::dropIfExists('otc_publish_sell');
    }
}
