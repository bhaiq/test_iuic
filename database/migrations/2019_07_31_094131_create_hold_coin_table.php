<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHoldCoinTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hold_coin', function (Blueprint $table) {
            $table->bigIncrements('uid');
            $table->unsignedDecimal('amount',30,8)->default(0.00)->comment('持币数量');
            $table->unsignedDecimal('price',16,8)->default(0.00)->comment('持币价格');
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
        Schema::dropIfExists('hold_coin');
    }
}
