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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Role key used in code (e.g., super_admin)');
            $table->string('name')->comment('Display name (e.g., Super Admin)');
            $table->text('description')->nullable()->comment('What this role can do');
            $table->string('color')->default('gray')->comment('Badge color: danger, warning, info, success, primary, gray');
            $table->json('permissions')->nullable()->comment('Array of permissions');
            $table->integer('sort_order')->default(0)->comment('Display order');
            $table->boolean('is_system')->default(false)->comment('System roles cannot be deleted');
            $table->boolean('is_active')->default(true)->comment('Active status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
