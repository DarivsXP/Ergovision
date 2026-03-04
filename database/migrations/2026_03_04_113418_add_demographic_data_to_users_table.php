<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('age')->nullable();
            $table->string('occupation')->nullable(); 
            $table->integer('daily_sitting_hours')->nullable();
            $table->boolean('has_musculoskeletal_issues')->default(false);
            $table->string('musculoskeletal_details')->nullable();
            $table->boolean('is_onboarded')->default(false); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
