<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('uid')->index();
            $table->unsignedTinyInteger('coin_id')->default(0)->comment('币种ID');
            $table->unsignedTinyInteger('type')->default(0)->comment('0 币币账户 1 法币账户');
            $table->decimal('amount', 30, 8)->default(0.00);
            $table->decimal('amount_freeze', 30, 8)->default(0.00);
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
        Schema::dropIfExists('account');
    }
}
