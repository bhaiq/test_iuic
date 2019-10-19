<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExTipTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ex_tip', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('num', 30, 8)->default(0)->comment('手续费数量');
            $table->decimal('bonus_num', 30, 8)->default(0)->comment('手续费分红数量');
            $table->unsignedInteger('order_id')->default(0)->comment('订单的ID');
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
        Schema::dropIfExists('ex_tip');
    }
}
