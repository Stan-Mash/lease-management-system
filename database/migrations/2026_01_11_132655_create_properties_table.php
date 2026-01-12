<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('property_code')->unique();
            $table->char('zone', 1)->default('A');
            $table->string('location')->nullable();

            // Link to Landlord
            $table->foreignId('landlord_id')->constrained()->onDelete('restrict');

            $table->decimal('management_commission', 5, 2)->default(10.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
