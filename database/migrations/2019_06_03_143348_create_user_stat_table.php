<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserStatTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_stat', function (Blueprint $table) {
            $table->bigIncrements('uid');
            $table->unsignedTinyInteger('invite_total')->default(0)->comment('总邀请');
            $table->unsignedTinyInteger('invite_today')->default(0)->comment('当天邀请');
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
        Schema::dropIfExists('user_stat');
    }
}
