<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSymbolHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('symbol_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('team_id')->default(0)->comment('交易对ID')->index();
            $table->unsignedDecimal('o', 30, 6)->default(0.00)->comment('开盘价');
            $table->unsignedDecimal('c', 30, 6)->default(0.00)->comment('收盘价');
            $table->unsignedDecimal('h', 30, 6)->default(0.00)->comment('最高价');
            $table->unsignedDecimal('l', 30, 6)->default(0.00)->comment('最低价');
            $table->unsignedDecimal('v', 30, 6)->default(0.00)->comment('成交量');
            $table->unsignedTinyInteger('type')->default(0)->comment('0 一分钟线; 1 15分钟线; 2 1小时; 3 4小时线; 5 1天线; 6 周线')->index();
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
        Schema::dropIfExists('symbol_history');
    }
}
