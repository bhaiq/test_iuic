<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAitcTransactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aitc_transaction', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('unit', 50)->comment('交易唯一值')->unique();
            $table->string('action', 20)->comment('交易类型，invalid 无效交易, received 接受, moved 内部转移, sent 发送');
            $table->decimal('amount', 50,8)->nullable(true)->comment('数量');
            $table->string('my_address', 60)->nullable(true)->comment('本钱包地址');
            $table->string('address_to', 60)->nullable(true)->comment('接受地址');
            $table->text('extends')->nullable(true);
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
        Schema::dropIfExists('aitc_transaction');
    }
}
