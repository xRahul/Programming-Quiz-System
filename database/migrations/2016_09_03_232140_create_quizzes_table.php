<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuizzesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->increments('id');

            $table->string('slug')->unique();
            $table->string('name');
            $table->longText('description');

            $table->boolean('timed');
            $table->tinyInteger('no_of_questions')->unsigned();
            $table->boolean('active_status');
            $table->tinyInteger('user_retries')->unsigned();

            $table->softDeletes();
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
        Schema::drop('quizzes');
    }
}
