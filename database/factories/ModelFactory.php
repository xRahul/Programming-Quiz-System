<?php

use QuizSystem\Models\Entities\User;
use QuizSystem\Models\Entities\Quiz;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$faker = new Faker\Generator();

$factory->define(User::class, function ($faker) {
    return [
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'email' => $faker->email,
        'mobile' => rand(8000000000, 9999999999),
        'password' => bcrypt('123456'),
        'remember_token' => str_random(10),
    ];
});

$factory->define(Quiz::class, function ($faker) {

		$faker->addProvider(
			new \BlogArticleFaker\FakerProvider(Faker\Factory::create())
		);

    return [
        'name' => $faker->company,
        'description' => $faker->articleContentMarkdown,
        'timed' => rand(0, 1),
        'no_of_questions' => rand(5, 20),
        'active_status' => rand(0, 1),
        'user_retries' => $faker->randomDigit,
    ];
});
