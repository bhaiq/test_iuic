<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuthenticationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('authentication', function (Blueprint $table) {
            $table->bigIncrements('uid');
            $table->string('name', 30)->default('')->comment('真实姓名');
            $table->string('number', 30)->default('')->comment('身份证号');
            $table->string('img_front', 255)->default('')->comment('身份正面照');
            $table->string('img_back', 255)->default('')->comment('身份反面照');
            $table->timestamps();
        });

        Schema::table('user', function (Blueprint $table) {
            $table->unsignedTinyInteger('is_auth')->default(0)->comment('实名认证：0 未实名认证 1 实名认证 , 2 申请中');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('authentication');
        Schema::table('user', function (Blueprint $table) {
            $table->dropColumn('is_auth');
        });
    }
}
