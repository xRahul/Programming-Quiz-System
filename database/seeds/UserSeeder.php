<?php

use Illuminate\Database\Seeder;
use QuizSystem\Models\Entities\Role;
use QuizSystem\Models\Entities\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->delete();

        $adminRole = Role::whereName('admin')->first();
        $userRole = Role::whereName('user')->first();
 
        $user = User::create(array(
            'first_name'    => 'Rahul',
            'last_name'     => 'Jain',
            'email'         => 'admin@laravelquiz.app',
            'password'      => bcrypt('qazxsw1!'),
            'mobile'		=> 7307307307
        ));
        $user->assignRole($adminRole);

        $user = User::create(array(
            'first_name'    => 'Rahul',
            'last_name'     => 'Jain',
            'email'         => 'user@laravelquiz.app',
            'password'      => bcrypt('qazxsw1!'),
            'mobile'		=> 7307307306
        ));
        $user->assignRole($userRole);
        
    }
}
