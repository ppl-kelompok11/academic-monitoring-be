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
        $data = [
            [
                "value" => 1,
            ],
            [
                "value" => 2,
            ],
            [
                "value" => 3,
            ],
            [
                "value" => 4,
            ],
            [
                "value" => 5,
            ], [
                "value" => 6,
            ],
            [
                "value" => 7,
            ],
            [
                "value" => 8,
            ],
            [
                "value" => 9,
            ],
            [
                "value" => 10,
            ],
            [
                "value" => 11,
            ],
            [
                "value" => 12,
            ],
            [
                "value" => 13,
            ],
            [
                "value" => 14,
            ]


        ];
        DB::table("semester")->upsert($data, ["value"]);
    }
}
