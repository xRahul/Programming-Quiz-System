<?php

use Illuminate\Database\Seeder;
use QuizSystem\Models\Entities\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('role_user')->delete();
        DB::table('roles')->delete();

        Role::create([
            'name'   => 'user'
        ]);
 
        Role::create([
            'name'   => 'admin'
        ]);
    }
}
