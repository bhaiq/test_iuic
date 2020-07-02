<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCoinExtractTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coin_extract', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('uid')->default(0)->comment('用户ID')->index();
            $table->unsignedInteger('coin_id')->default(0)->comment('币种ID');
            $table->decimal('coin_num', 30, 8)->default(0)->comment('提取的币种数量');
            $table->decimal('charge', 30, 8)->default(0)->comment('提取的手续费');
            $table->string('address')->default('')->comment('提取地址');
            $table->decimal('final_num', 30, 8)->default(0)->comment('到账数量');
            $table->unsignedTinyInteger('status')->default(0)->comment('0为申请中 1为成功 9为失败');
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
        Schema::dropIfExists('coin_extract');
    }
}
