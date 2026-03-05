<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lease_documents', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
        });
        Schema::table('lease_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('uploaded_by')->nullable()->change();
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lease_documents', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
        });
        Schema::table('lease_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('uploaded_by')->nullable(false)->change();
            $table->foreign('uploaded_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
