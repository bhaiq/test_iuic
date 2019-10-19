<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccessTokenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('access_token', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('token')->default('');
            $table->unsignedBigInteger('uid')->default(0)->comment('用户id');
            $table->string('uuid')->default('')->comment('设备ID');
            $table->string('ip')->default('')->comment('登录时ip');
            $table->unsignedTinyInteger('type')->default(0)->comment('设备类型 1 iOS 2 Android');
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
        Schema::dropIfExists('access_token');
    }
}
