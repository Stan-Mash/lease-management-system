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
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Zone A", "Zone B", "Westlands Zone"
            $table->string('code')->unique(); // e.g., "ZN-A", "ZN-B"
            $table->text('description')->nullable();
            $table->foreignId('zone_manager_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // For additional zone data
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'code']);
        });

        // Add zone_id to users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('zone_id')->nullable()->after('role')->constrained('zones')->onDelete('set null');
            $table->index('zone_id');
        });

        // Add zone_id to leases table
        Schema::table('leases', function (Blueprint $table) {
            $table->foreignId('zone_id')->nullable()->after('landlord_id')->constrained('zones')->onDelete('set null');
            $table->foreignId('assigned_field_officer_id')->nullable()->after('zone_id')->constrained('users')->onDelete('set null');
            $table->index(['zone_id', 'assigned_field_officer_id']);
        });

        // Add zone_id to landlords table if it exists
        if (Schema::hasTable('landlords')) {
            Schema::table('landlords', function (Blueprint $table) {
                $table->foreignId('zone_id')->nullable()->after('id')->constrained('zones')->onDelete('set null');
                $table->index('zone_id');
            });
        }

        // Add zone_id to properties table if it exists
        if (Schema::hasTable('properties')) {
            Schema::table('properties', function (Blueprint $table) {
                $table->foreignId('zone_id')->nullable()->after('landlord_id')->constrained('zones')->onDelete('set null');
                $table->index('zone_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove foreign keys from related tables
        if (Schema::hasTable('properties') && Schema::hasColumn('properties', 'zone_id')) {
            Schema::table('properties', function (Blueprint $table) {
                $table->dropForeign(['zone_id']);
                $table->dropColumn('zone_id');
            });
        }

        if (Schema::hasTable('landlords') && Schema::hasColumn('landlords', 'zone_id')) {
            Schema::table('landlords', function (Blueprint $table) {
                $table->dropForeign(['zone_id']);
                $table->dropColumn('zone_id');
            });
        }

        Schema::table('leases', function (Blueprint $table) {
            $table->dropForeign(['zone_id']);
            $table->dropForeign(['assigned_field_officer_id']);
            $table->dropColumn(['zone_id', 'assigned_field_officer_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['zone_id']);
            $table->dropColumn('zone_id');
        });

        Schema::dropIfExists('zones');
    }
};
