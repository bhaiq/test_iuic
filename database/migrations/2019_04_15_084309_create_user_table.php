<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nickname', 30)->default('')->comment('昵称');
            $table->string('email', 50)->default('')->comment('电子邮箱');
            $table->string('avatar', 255)->default('')->comment('头像');
            $table->string('mobile', 15)->default('')->comment('手机号码');
            $table->string('int_code', 10)->default('86')->comment('国际冠码');
            $table->string('password')->comment('密码');
            $table->string('transaction_password')->default('')->comment('交易密码');
            $table->tinyInteger('status')->default('1')->comment('0:禁用;1启用');
            $table->integer('pid')->default('0')->comment('上级ID')->index();
            $table->text('pid_path')->nullable()->comment('上级路径');
            $table->string('invite_code')->default('')->comment('邀请码');
            $table->unsignedTinyInteger('type')->default(0)->comment('用户类型 0：普通用户 1:管理员');

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
        Schema::dropIfExists('user');
    }
}
