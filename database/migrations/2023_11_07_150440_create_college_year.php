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
        Schema::create('college_years', function (Blueprint $table) {
            $table->id();
            $table->string('year');
            $table->string('semester');
            $table->boolean('active')->default(false);
            $table->timestamps();
            $table->unique(['year', 'semester']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('college_years');
    }
};
