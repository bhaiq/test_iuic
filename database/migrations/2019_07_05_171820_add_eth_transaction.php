<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEthTransaction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('eth_transaction', function (Blueprint $table) {
            $table->integer('coin_id')->comment('币种id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('eth_transaction', function (Blueprint $table) {
            $table->dropColumn('coin_id');
        });
    }
}
