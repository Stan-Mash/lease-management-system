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
        Schema::create('lease_sequences', function (Blueprint $table) {
            $table->id();
            $table->char('zone', 1); // A-G
            $table->integer('year'); // 2026, 2027, etc.
            $table->string('lease_type', 30); // commercial, residential_micro, residential_major, landlord_provided
            $table->integer('last_sequence')->default(0); // Last used sequence number
            $table->timestamps();

            // Unique constraint: one sequence per zone/year/type combination
            $table->unique(['zone', 'year', 'lease_type']);

            // Index for faster lookups
            $table->index(['zone', 'year', 'lease_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lease_sequences');
    }
};
