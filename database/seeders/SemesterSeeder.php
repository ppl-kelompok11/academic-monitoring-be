<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SemesterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $data = [[
            "id" => 1,
        ], [
            "id" => 2,
        ],
        [
            
        ]
    
    ];
        DB::table("users")->upsert($data, ["email"]);
    }
}
