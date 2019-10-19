<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInfoCollectTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('info_collect', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('cur_date')->nullable()->comment('记录时间');
            $table->unsignedInteger('zc_user_count')->default(0)->comment('新增注册用户数量');
            $table->unsignedInteger('gj_user_count')->default(0)->comment('新增高级用户数量');
            $table->unsignedInteger('pt_user_count')->default(0)->comment('新增普通用户数量');
            $table->decimal('today_release', 30, 8)->default(0)->comment('单日释放');
            $table->decimal('today_trade', 30, 8)->default(0)->comment('单日交易');
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
        Schema::dropIfExists('info_collect');
    }
}
