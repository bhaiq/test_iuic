<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserWalletLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_wallet_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('uid')->nullable()->comment('用户ID')->index();
            $table->string('dy_table')->nullable()->comment('对应的表名');
            $table->unsignedInteger('dy_id')->nullable()->comment('对应的表ID');
            $table->string('exp')->nullable()->comment('说明');
            $table->enum('sign', ['+', '-'])->comment('余额变化的加减');
            $table->decimal('num', 30, 8)->default(0)->comment('金额');
            $table->unsignedTinyInteger('wallet_type')->default(0)->comment('钱包类型 1释放的IUIC 2矿池的IUIC');
            $table->unsignedTinyInteger('log_type')->default(0)->comment('日志类型 1是矿池');
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
        Schema::dropIfExists('user_wallet_logs');
    }
}
