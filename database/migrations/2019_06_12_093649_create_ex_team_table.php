<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExTeamTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ex_team', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 50)->default('')->comment('名称');
            $table->unsignedTinyInteger('coin_id_goods')->default(0)->comment('交易币ID');
            $table->unsignedTinyInteger('coin_id_legal')->default(0)->comment('法币ID');
            $table->unsignedTinyInteger('status')->default(0)->comment('状态：0正常交易，1 维护中，2关闭');
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
        Schema::dropIfExists('ex_team');
    }
}
