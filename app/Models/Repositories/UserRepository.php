<?php

namespace QuizSystem\Models\Repositories;

use DB;
use QuizSystem\Models\Entities\User;
use QuizSystem\Models\Entities\Role;
use QuizSystem\Models\Interfaces\IUserRepository;

class UserRepository implements IUserRepository {

    public function getAllUsers()
    {
        return User::all();
    }

    public function createUser($paramsArray)
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'first_name' => ucwords(strtolower($paramsArray['first_name'])),
                'last_name' => ucwords(strtolower($paramsArray['last_name'])),
                'email' => strtolower($paramsArray['email']),
                'password' => bcrypt($paramsArray['password']),
                'mobile' => intval($paramsArray['mobile'])
            ]);
            //Assign user role as default, or admin if admin_user flag is set
            $roleName = 'user';
            if( array_key_exists('admin_user' , $paramsArray) 
            	&& $paramsArray['admin_user'] == 1 ) 
            {
            	$roleName = 'admin';
            }
            $role = Role::whereName($roleName)->first();
            $user->assignRole($role);
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
        DB::commit();
        return true;
    }

    // to use any function of Eloquent class directly
    public function __call($method, $args)
    {
        return call_user_func_array([$this->user, $method], $args);
    }


}