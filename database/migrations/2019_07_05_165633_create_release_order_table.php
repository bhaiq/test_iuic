<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReleaseOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('release_order', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('uid')->default(0)->comment('用户ID')->index();
            $table->decimal('total_num', 30, 2)->default(0.00)->comment('赠送的矿池总量');
            $table->decimal('release_num', 30, 2)->default(0.00)->comment('矿池释放的总量');
            $table->decimal('today_max', 30, 2)->default(0.00)->comment('当日释放的最大数量');
            $table->decimal('today_num', 30, 2)->default(0.00)->comment('当日释放的数量');
            $table->dateTime('release_time')->nullable()->comment('释放时间');
            $table->unsignedTinyInteger('status')->default(0)->comment('0是待释放 1是释放完成');
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
        Schema::dropIfExists('release_order');
    }
}
