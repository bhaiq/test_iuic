<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateModeOfPaymentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mode_of_payment', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('uid');
            $table->string('qr_code')->default('')->comment('二维码');
            $table->string('number')->default('')->comment('支付号码');
            $table->string('name')->default('')->comment('名字');
            $table->tinyInteger('type')->default(0)->comment('支付类型 0 微信 1 支付宝 2 银行卡');
            $table->string('bank')->default('')->comment('银行信息');
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
        Schema::dropIfExists('mode_of_payment');
    }
}
