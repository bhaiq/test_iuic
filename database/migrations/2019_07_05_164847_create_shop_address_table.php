<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopAddressTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_address', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('uid')->default(0)->comment('用户ID')->index();
            $table->string('to_name')->default('')->comment('收货人名称');
            $table->string('to_mobile')->default('')->comment('收货人手机');
            $table->string('to_address')->default('')->comment('收货人地址');
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
        Schema::dropIfExists('shop_address');
    }
}
