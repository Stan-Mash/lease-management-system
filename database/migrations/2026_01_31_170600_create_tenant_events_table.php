<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the tenant_events table for the Tenant 360 CRM Timeline.
 *
 * This table stores all communication and activity events for tenants,
 * supporting polymorphic relationships to various source models (Lease, SMS, etc.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_events', function (Blueprint $table) {
            $table->id();

            // Core tenant relationship
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            // Event classification
            $table->string('event_type', 50)
                ->comment('Type: sms, email, note, system, financial, dispute, call, visit, document, lease');

            // Event content
            $table->string('title', 255);
            $table->jsonb('body')->nullable()
                ->comment('Structured event data - format varies by event_type');

            // Polymorphic relationship to source record (optional)
            // e.g., Lease, SMSLog, Payment, Dispute models
            $table->nullableMorphs('eventable');

            // When the event actually occurred (may differ from created_at)
            $table->timestamp('happened_at')
                ->useCurrent()
                ->comment('When the event actually occurred');

            // Actor tracking - who/what triggered this event
            $table->foreignId('performed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User who performed/recorded the event');

            // Visibility and status flags
            $table->boolean('is_internal')->default(false)
                ->comment('Internal notes not visible to tenant');
            $table->boolean('is_pinned')->default(false)
                ->comment('Pinned events appear at top of timeline');
            $table->boolean('requires_follow_up')->default(false);
            $table->timestamp('follow_up_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            // Metadata for integrations
            $table->string('external_reference', 100)->nullable()
                ->comment('Reference ID from external systems (CHIPS, Africa\'s Talking, etc.)');
            $table->string('channel', 50)->nullable()
                ->comment('Communication channel: africas_talking, email_provider, manual, system');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index(['tenant_id', 'happened_at'], 'tenant_events_tenant_timeline');
            $table->index(['tenant_id', 'event_type'], 'tenant_events_tenant_type');
            $table->index(['tenant_id', 'is_pinned', 'happened_at'], 'tenant_events_pinned');
            $table->index(['tenant_id', 'requires_follow_up', 'resolved_at'], 'tenant_events_follow_up');
            $table->index('external_reference', 'tenant_events_external_ref');
            $table->index('happened_at', 'tenant_events_chronological');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_events');
    }
};
