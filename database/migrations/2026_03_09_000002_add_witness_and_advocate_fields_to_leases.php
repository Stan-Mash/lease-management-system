<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->string('lessee_witness_name')->nullable()->after('signature_longitude');
            $table->string('lessee_witness_id')->nullable()->after('lessee_witness_name');
            $table->string('lessor_witness_name')->nullable()->after('lessee_witness_id');
            $table->string('lessor_witness_id')->nullable()->after('lessor_witness_name');
            $table->string('tenant_advocate_name')->nullable()->after('lessor_witness_id');
            $table->string('tenant_advocate_email')->nullable()->after('tenant_advocate_name');
        });
    }

    public function down(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->dropColumn([
                'lessee_witness_name',
                'lessee_witness_id',
                'lessor_witness_name',
                'lessor_witness_id',
                'tenant_advocate_name',
                'tenant_advocate_email',
            ]);
        });
    }
};

