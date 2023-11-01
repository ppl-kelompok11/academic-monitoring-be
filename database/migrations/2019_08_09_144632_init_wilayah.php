<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InitWilayah extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        ini_set('memory_limit', -1);

        $contents = file_get_contents(__DIR__ . '/sql/wilayah.sql');

        $statment = explode(";", $contents);

        foreach ($statment as $sql) {
            if (trim($sql) != "") {
                // echo $sql;
                DB::statement($sql);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        ini_set('memory_limit', -1);

        $contents = file_get_contents(__DIR__ . '/sql/wilayah_down.sql');

        $statment = explode(";", $contents);

        foreach ($statment as $sql) {
            if (trim($sql) != "") {
                // echo $sql;
                DB::statement($sql);
            }
        }
    }
}
