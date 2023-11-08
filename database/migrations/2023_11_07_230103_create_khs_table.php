<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('khs', function (Blueprint $table) {
            $table->id();
            $table->integer('semester');
            $table->integer('sks');
            $table->integer('sks_kumulatif');
            $table->text('scan_khs');
            $table->integer('ip');
            $table->integer('ipk');
            $table->bigInteger('irs_id')->unsigned();
            $table->string('verification_status');
            $table->bigInteger('student_id')->unsigned();
            $table->bigInteger('college_year_id')->unsigned();
            $table->bigInteger('created_by')->unsigned();
            $table->bigInteger('updated_by')->unsigned();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('khs');
    }
};
