<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $data = [
            "name" => "Admin",
            "email" => "admin@gmail.com",
            "password" => bcrypt("Admin1234!"),
            "role_id" => 1,
        ];
        DB::table("users")->upsert($data, ["email"]);
    }
}
