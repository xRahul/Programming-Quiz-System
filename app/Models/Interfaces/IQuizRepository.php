<?php

namespace QuizSystem\Models\Interfaces;

interface IQuizRepository {

    // create user function that accepts array of parameters
    public function createQuiz($paramsArray);

}