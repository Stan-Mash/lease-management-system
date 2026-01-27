<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lease_lawyer_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lawyer_id')->constrained()->cascadeOnDelete();

            // Sending to lawyer
            $table->enum('sent_method', ['email', 'physical'])->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('sent_notes')->nullable();

            // Receiving from lawyer
            $table->enum('returned_method', ['email', 'physical'])->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('returned_notes')->nullable();

            // Tracking
            $table->integer('turnaround_days')->nullable();
            $table->enum('status', ['pending', 'sent', 'returned', 'cancelled'])->default('pending');

            $table->timestamps();

            $table->index(['lease_id', 'status']);
            $table->index('lawyer_id');
            $table->index('sent_at');
            $table->index('returned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lease_lawyer_tracking');
    }
};
