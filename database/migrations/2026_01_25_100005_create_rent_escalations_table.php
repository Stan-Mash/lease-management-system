<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rent_escalations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_id')->constrained()->cascadeOnDelete();
            $table->date('effective_date');
            $table->decimal('previous_rent', 12, 2);
            $table->decimal('new_rent', 12, 2);
            $table->decimal('increase_percentage', 5, 2);
            $table->boolean('applied')->default(false);
            $table->timestamp('applied_at')->nullable();
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('tenant_notified')->default(false);
            $table->timestamp('tenant_notified_at')->nullable();
            $table->boolean('landlord_notified')->default(false);
            $table->timestamp('landlord_notified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['lease_id', 'effective_date']);
            $table->index(['effective_date', 'applied']);
            $table->index('applied');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_escalations');
    }
};
