<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CollegeYearSeeder extends Seeder
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
            "year" => "2023",
            "semester" => "genap",
            "active" => true,
        ];
        DB::table("college_years")->upsert($data, ["year", "semester"]);
    }
}
