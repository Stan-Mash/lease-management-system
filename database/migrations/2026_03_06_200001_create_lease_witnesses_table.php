<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Witness records for commercial lease signature pages.
 *
 * Each lease may have up to two witness records:
 *  - 'tenant'  → person who witnessed the Lessee (tenant) sign
 *  - 'lessor'  → person who witnessed the Lessor (Chabrin/PM) sign
 *
 * For digital leases the OTP + IP trail already satisfies identity verification,
 * but the witness record creates the physical-world attestation trail needed for
 * advocate review and Lands Registry registration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lease_witnesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_id')->constrained()->cascadeOnDelete();

            // Which signatory did this witness observe?
            $table->enum('witnessed_party', ['tenant', 'lessor'])
                ->comment("'tenant' = Lessee signing, 'lessor' = Lessor/PM signing");

            // Who is the witness?
            $table->foreignId('witnessed_by_user_id')->nullable()->constrained('users')->nullOnDelete()
                ->comment('Chabrin staff member (internal users only)');
            $table->string('witnessed_by_name')
                ->comment('Full name at time of witnessing — preserved even if user is renamed');
            $table->string('witnessed_by_title')->nullable()
                ->comment('Job title / role at time of witnessing, e.g. "Lease Officer, Chabrin Agencies Ltd"');

            // Category of witness
            $table->enum('witness_type', ['staff', 'advocate', 'external'])->default('staff')
                ->comment("'staff' = Chabrin employee, 'advocate' = LSK advocate, 'external' = other");

            // For advocate witnesses: LSK practising certificate number
            $table->string('lsk_number')->nullable()
                ->comment('LSK practising certificate number (for advocate witnesses only)');

            // Witnessing details
            $table->timestamp('witnessed_at')
                ->comment('Timestamp when witness confirmed they observed the signing');
            $table->string('ip_address', 45)->nullable()
                ->comment('IP of the staff member who recorded the witness event');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['lease_id', 'witnessed_party']);
            $table->index('witnessed_at');
            $table->index('witness_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lease_witnesses');
    }
};
