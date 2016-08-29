<?php 

namespace QuizSystem\Models;
 
use Illuminate\Support\ServiceProvider;
 
class ModelServiceProvider extends ServiceProvider {

    public function register()
    {
        $this->app->bind(
        	'QuizSystem\\Models\\Interfaces\\IUserRepository', 
        	'QuizSystem\\Models\\Repositories\\UserRepository'
        );
    }
}