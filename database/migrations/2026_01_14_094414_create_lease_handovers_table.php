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
        Schema::create('lease_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_id')->constrained()->onDelete('cascade');
            $table->foreignId('field_officer_id')->constrained('users')->onDelete('cascade'); // FO assigned
            $table->foreignId('checked_out_by')->nullable()->constrained('users')->onDelete('set null'); // Who gave it to FO (ZM/PM)

            // Checkout tracking
            $table->timestamp('checked_out_at')->nullable(); // When FO received document
            $table->string('checkout_status', 20)->default('pending'); // pending, checked_out, delivered, returned

            // Delivery tracking
            $table->timestamp('delivered_at')->nullable(); // When delivered to tenant
            $table->string('delivery_status', 20)->nullable(); // successful, tenant_unavailable, tenant_refused, other
            $table->text('delivery_notes')->nullable(); // Notes about delivery attempt

            // Signature tracking
            $table->timestamp('signed_at')->nullable(); // When tenant signed
            $table->boolean('signature_obtained')->default(false); // Whether tenant signed
            $table->text('signature_notes')->nullable(); // Notes about signature

            // Return tracking
            $table->timestamp('returned_at')->nullable(); // When FO returned to office
            $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('set null'); // Who received it back (ZM/PM)
            $table->string('return_condition', 50)->nullable(); // signed, unsigned, damaged, lost
            $table->text('return_notes')->nullable(); // Notes about return

            // Additional tracking
            $table->integer('delivery_attempts')->default(0); // Number of delivery attempts
            $table->decimal('mileage', 8, 2)->nullable(); // Mileage for reimbursement
            $table->text('issues_encountered')->nullable(); // Any problems during process

            $table->timestamps();

            // Indexes for common queries
            $table->index('lease_id');
            $table->index('field_officer_id');
            $table->index('checkout_status');
            $table->index('checked_out_at');
            $table->index('returned_at');
            $table->index(['field_officer_id', 'checkout_status']); // FO's current assignments
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lease_handovers');
    }
};
