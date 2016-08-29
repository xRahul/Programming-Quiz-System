<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRoleUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('role_user', function (Blueprint $table) {
            $table->increments('id');
            
            $table->integer('user_id')->unsigned()->index();
            $table->integer('role_id')->unsigned()->index();
            
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('user_id')
                    ->references('id')
                    ->on('users');
            $table->foreign('role_id')
                    ->references('id')
                    ->on('roles');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('role_user');
    }
}
