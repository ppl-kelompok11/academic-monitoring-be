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
            [
                "email" => "admin@gmail.com",
                "password" => bcrypt("Admin1234!"),
                "role_id" => 1,
                "ref_id" => 1,
                "ref_table" => "admin",
            ],
            [
                "email" => "if.undip@gmail.com",
                "password" => bcrypt("ifundip1234!"),
                "role_id" => 4,
                "ref_id" => 1,
                "ref_table" => "department",
            ]
        ];
        DB::table("users")->upsert($data, ["email"]);
    }
}
