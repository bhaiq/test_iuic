<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAccountLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_log', function (Blueprint $table) {
            $table->unsignedTinyInteger('coin_type')->default(0)->comment('0为币币 1为法币');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('account_log', function (Blueprint $table) {
            $table->dropColumn('coin_type');
        });
    }
}
