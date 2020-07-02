<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCoinTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coin', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 50)->default('')->comment('币名');
            $table->unsignedTinyInteger('is_legal')->default(0)->comment('是否为法币:0 否；1 是');
            $table->unsignedTinyInteger('status')->default(0)->comment('状态：0正常，1 维护中，2关闭');
            $table->text('coin_types')->nullable()->comment('币信息');
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
        Schema::dropIfExists('coin');
    }
}
