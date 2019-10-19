<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuthBusinessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auth_business', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('uid')->nullable()->comment('用户ID')->index();
            $table->unsignedTinyInteger('coin_id')->default(0)->index();
            $table->unsignedTinyInteger('coin_type')->default(0)->comment('币种类型 1为法币 0为币币');
            $table->decimal('amount', 30, 8)->default(0.00);
            $table->unsignedTinyInteger('status')->default(0)->comment('0为申请中 1为成功 2为申请取消认证');
            $table->timestamps();
        });

        Schema::table('user', function (Blueprint $table) {
            $table->unsignedTinyInteger('is_business')->default(0)->comment('商家认证：0为未认证, 1为认证, 2为申请中');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('auth_business');

        Schema::table('user', function (Blueprint $table) {
            $table->dropColumn('is_business');
        });
    }
}
