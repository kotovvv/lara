<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;
use Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        if (!DB::table('roles')->where('name', 'Administrator')->value('id')) {
            DB::table('roles')->insert([
                ['name' => 'Administrator'],
                ['name' => 'CRM manager'],
                ['name' => 'Manager']
            ]);
        }
        $role_id = DB::table('roles')->where('name', 'Administrator')->value('id');

        DB::table('users')->insert([
            [
                'name' => 'lara',
                'password' => Hash::make('lara'),
                'role_id' => $role_id,
                'status' => 1
            ],
            [
                'name' => 'larac',
                'password' => Hash::make('lara'),
                'role_id' => 2,
                'status' => 1
            ],
            [
                'name' => 'laram',
                'password' => Hash::make('lara'),
                'role_id' => 3,
                'status' => 1
            ],

        ]);
    }
}
