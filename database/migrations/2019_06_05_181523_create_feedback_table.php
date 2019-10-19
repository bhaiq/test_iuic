<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFeedbackTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('uid')->default(0)->comment('用户ID');
            $table->string('title')->default('')->comment('标题');
            $table->text('description')->nullable()->comment('描述');
            $table->unsignedTinyInteger('status')->default(0)->comment('状态：0未解决，1已解决');
            $table->text('img')->nullable()->comment('图片');
            $table->timestamps();
        });

        Schema::create('feedback_comment', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('feedback_id')->default(0)->comment('反馈ID');
            $table->unsignedBigInteger('uid')->default(0)->comment('用户ID');
            $table->unsignedBigInteger('service_id')->default(0)->comment('用户ID');
            $table->unsignedTinyInteger('type')->default(0)->comment('类型：0 用户，1 客服');
            $table->text('description')->nullable()->comment('描述');
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
        Schema::dropIfExists('feedback');
        Schema::dropIfExists('feedback_comment');
    }
}
