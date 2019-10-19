<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('uid')->default(0)->index();
            $table->unsignedTinyInteger('coin_id')->default(0)->index();
            $table->decimal('amount', 30, 8)->default(0.00);
            $table->unsignedTinyInteger('scene')->default(0)->comment('1 充值 2 提币 3 币币转法币 4 法币转币币 5 交易划出 6 交易收入 7 交易取消 8 交易返还 9 法币划出 10 法币买入 11 法币取消');
            $table->unsignedTinyInteger('type')->default(0)->comment('0 减少 1 增加');
            $table->string('remark')->default('');
            $table->text('extend')->nullable();
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
        Schema::dropIfExists('account_log');
    }
}
