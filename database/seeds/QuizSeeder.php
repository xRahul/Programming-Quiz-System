<?php

use Illuminate\Database\Seeder;
use QuizSystem\Models\Entities\Quiz;

class QuizSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('quizzes')->delete();
        factory(Quiz::class, 46)->create();
    }
}
