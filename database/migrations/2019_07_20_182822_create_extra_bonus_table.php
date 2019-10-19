<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExtraBonusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('extra_bonus', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable()->comment('名称');
            $table->decimal('tip', 10, 2)->default(0)->comment('奖励百分比');
            $table->text('users')->nullable()->comment('奖励的用户ID');
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
        Schema::dropIfExists('extra_bonus');
    }
}
