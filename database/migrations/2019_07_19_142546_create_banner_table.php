<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBannerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('banner', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('title')->nullable()->comment('标题');
            $table->string('img_url')->nullable()->comment('图片地址');
            $table->unsignedTinyInteger('top')->default(0)->comment('位置权重');
            $table->unsignedTinyInteger('type')->default(0)->comment('类型 0为单图片 1为带内部链接 2为带外部链接');
            $table->unsignedTinyInteger('jump_type')->default(0)->comment('内部跳转类型 0为跳转分享页面');
            $table->string('jump_url')->nullable()->comment('跳转地址');

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
        Schema::dropIfExists('banner');
    }
}
