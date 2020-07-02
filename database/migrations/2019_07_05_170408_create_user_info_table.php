<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserInfoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_info', function (Blueprint $table) {
            $table->unsignedBigInteger('uid')->default(0)->comment('用户ID')->index();
            $table->integer('pid')->default('0')->comment('上级ID')->index();
            $table->text('pid_path')->nullable()->comment('上级路径');
            $table->unsignedTinyInteger('level')->default(1)->comment('用户类型 1：普通用户 2:高级用户');
            $table->decimal('buy_total', 30, 8)->default(0)->comment('购买赠送的矿池总数');
            $table->unsignedInteger('buy_count')->default(0)->comment('购买的商品价值总数');
            $table->decimal('release_total', 30, 8)->default(0)->comment('矿池释放的总数');
            $table->dateTime('release_time')->nullable()->comment('最新释放时间');
            $table->unsignedTinyInteger('today_count')->default(0)->comment('今日释放次数');
            $table->unsignedTinyInteger('is_bonus')->default(0)->comment('是否有分红奖 0是没有 1是有');
            $table->unsignedTinyInteger('is_admin')->default(0)->comment('是否有管理奖 0是没有 1是有');
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
        Schema::dropIfExists('user_info');
    }
}
