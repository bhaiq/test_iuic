<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('news', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title')->default('')->comment('标题');
            $table->string('thumbnail')->default('')->comment('标题图');
            $table->text('content')->nullable()->comment('内容');
            $table->string('type_name')->default('')->comment('分类名称');
            $table->unsignedTinyInteger('type')->default(0)->comment('分类');
            $table->unsignedTinyInteger('language')->default(0)->comment('语言 0 中文 1英文');
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
        Schema::dropIfExists('news');
    }
}
