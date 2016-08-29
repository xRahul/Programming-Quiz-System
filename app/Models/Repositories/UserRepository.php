<?php

namespace QuizSystem\Models\Repositories;

use QuizSystem\Models\Entities\User;
use QuizSystem\Models\Interfaces\IUserRepository;

class UserRepository implements IUserRepository {

    public function getAllUsers()
    {
        return User::all();
    }

    // to use any function of Eloquent class directly
    public function __call($method, $args)
    {
        return call_user_func_array([$this->user, $method], $args);
    }


}