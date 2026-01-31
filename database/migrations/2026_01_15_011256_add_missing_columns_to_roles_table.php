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
        Schema::table('roles', function (Blueprint $table) {
            // Add sort_order column if it doesn't exist
            if (! Schema::hasColumn('roles', 'sort_order')) {
                $table->integer('sort_order')->default(0)->comment('Display order');
            }

            // Add is_active column if it doesn't exist
            if (! Schema::hasColumn('roles', 'is_active')) {
                $table->boolean('is_active')->default(true)->comment('Active status');
            }

            // Add color column if it doesn't exist
            if (! Schema::hasColumn('roles', 'color')) {
                $table->string('color')->default('gray')->comment('Badge color');
            }

            // Add permissions column if it doesn't exist
            if (! Schema::hasColumn('roles', 'permissions')) {
                $table->json('permissions')->nullable()->comment('Array of permissions');
            }

            // Add is_system column if it doesn't exist
            if (! Schema::hasColumn('roles', 'is_system')) {
                $table->boolean('is_system')->default(false)->comment('System roles cannot be deleted');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $columns = ['sort_order', 'is_active', 'color', 'permissions', 'is_system'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('roles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
