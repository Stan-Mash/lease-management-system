<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lease_lawyer_tracking', function (Blueprint $table) {
            $table->string('lawyer_link_token', 64)->nullable()->unique()->after('status')
                ->comment('Token for lawyer portal link (download + upload stamped PDF)');
            $table->timestamp('lawyer_link_expires_at')->nullable()->after('lawyer_link_token')
                ->comment('When the lawyer portal link expires');
            $table->boolean('sent_via_portal_link')->default(false)->after('lawyer_link_expires_at')
                ->comment('True when lawyer was sent the portal link instead of PDF attachment');
        });
    }

    public function down(): void
    {
        Schema::table('lease_lawyer_tracking', function (Blueprint $table) {
            $table->dropColumn(['lawyer_link_token', 'lawyer_link_expires_at', 'sent_via_portal_link']);
        });
    }
};
