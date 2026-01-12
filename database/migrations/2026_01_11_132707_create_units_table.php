<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();

            // Link to Parent Property
            $table->foreignId('property_id')->constrained()->onDelete('cascade');

            // Core Unit Details
            $table->string('unit_number');
            $table->string('type')->nullable();
            $table->decimal('market_rent', 12, 2);
            $table->decimal('deposit_required', 12, 2)->default(0);

            $table->enum('status', ['VACANT', 'OCCUPIED', 'MAINTENANCE'])->default('VACANT');
            $table->unique(['property_id', 'unit_number']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
