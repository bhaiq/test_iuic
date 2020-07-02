<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateArticleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('article', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedTinyInteger('type')->default(0)->comment('类型');
            $table->string('title')->nullable()->comment('标题');
            $table->string('url')->nullable()->comment('外链地址');
            $table->text('content')->nullable()->comment('内容');
            $table->string('source')->nullable()->comment('来源');
            $table->string('thumbnail')->nullable()->comment('缩略图');
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
        Schema::dropIfExists('article');
    }
}
