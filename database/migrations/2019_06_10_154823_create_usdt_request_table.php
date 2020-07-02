<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsdtRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('usdt_extract', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('uid')->index();
            $table->unsignedTinyInteger('type')->default(1)->comment('类型 1 usdt');
            $table->unsignedTinyInteger('handle_type')->default(0)->comment('操作类型 0 未操作 1 手动 2 自动');
            $table->decimal('amount', 30, 8)->default(0.00)->comment('数量');
            $table->string('address', 50)->default('')->comment('提币地址');
            $table->decimal('charge', 30, 8)->default(1.00)->comment('手续费');
            $table->unsignedTinyInteger('state')->default(0)->comment('类型 0 审核中 1进行中 2: 已完成 3: 已退回');
            $table->unsignedTinyInteger('status')->default(0)->comment('类型 0 未操作 1: 打包中 2: 已完成 3: 失败');
            $table->text('transaction_message')->nullable(true)->comment('区块交易信息');
            $table->string('remark')->nullable(true)->comment('说明');
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
        Schema::dropIfExists('usdt_extract');
    }
}
