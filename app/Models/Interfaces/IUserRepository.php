<?php

namespace QuizSystem\Models\Interfaces;

interface IUserRepository {
    
    // create user function that accepts array of parameters
    public function createUser($paramsArray);

}