<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lease_templates', function (Blueprint $table) {
            $table->json('pdf_coordinate_map')->nullable()->after('source_pdf_path');
        });
    }

    public function down(): void
    {
        Schema::table('lease_templates', function (Blueprint $table) {
            $table->dropColumn('pdf_coordinate_map');
        });
    }
};
