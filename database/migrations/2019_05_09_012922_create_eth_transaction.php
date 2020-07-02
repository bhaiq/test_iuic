<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEthTransaction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('eth_transaction', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('hash')->comment('hash');
            $table->string('block')->comment('区块高度');
            $table->string('from')->comment('发送地址');
            $table->string('to')->comment('接手地址');
            $table->string('amount')->comment('接收数量');

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
        Schema::dropIfExists('eth_transaction');
    }
}
