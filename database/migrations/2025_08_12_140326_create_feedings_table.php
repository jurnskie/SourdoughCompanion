<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('feedings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('starter_id')->constrained()->onDelete('cascade');
            $table->integer('day');
            $table->integer('starter_amount'); // in grams
            $table->integer('flour_amount'); // in grams
            $table->integer('water_amount'); // in grams
            $table->string('ratio'); // e.g., "1:5:5"
            $table->json('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedings');
    }
};
