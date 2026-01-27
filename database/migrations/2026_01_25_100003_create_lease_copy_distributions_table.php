<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lease_copy_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_id')->constrained()->cascadeOnDelete();

            // Tenant copy
            $table->enum('tenant_copy_method', ['email', 'physical', 'both'])->nullable();
            $table->timestamp('tenant_copy_sent_at')->nullable();
            $table->foreignId('tenant_copy_sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('tenant_copy_confirmed')->default(false);
            $table->timestamp('tenant_copy_confirmed_at')->nullable();

            // Landlord copy
            $table->enum('landlord_copy_method', ['email', 'physical', 'both'])->nullable();
            $table->timestamp('landlord_copy_sent_at')->nullable();
            $table->foreignId('landlord_copy_sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('landlord_copy_confirmed')->default(false);
            $table->timestamp('landlord_copy_confirmed_at')->nullable();

            // Chabrin office copy
            $table->boolean('office_copy_filed')->default(false);
            $table->timestamp('office_copy_filed_at')->nullable();
            $table->foreignId('office_copy_filed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('lease_id');
            $table->index('tenant_copy_sent_at');
            $table->index('landlord_copy_sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lease_copy_distributions');
    }
};
