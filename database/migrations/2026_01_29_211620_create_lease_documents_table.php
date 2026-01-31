<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lease_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_id')->constrained()->onDelete('cascade');
            $table->string('document_type'); // original_signed, amendment, addendum, notice, other
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size'); // in bytes
            $table->unsignedBigInteger('compressed_size')->nullable(); // after compression
            $table->string('file_hash')->nullable(); // SHA256 for integrity
            $table->boolean('is_compressed')->default(false);
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('document_date')->nullable(); // date on the physical document
            $table->timestamps();

            $table->index(['lease_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lease_documents');
    }
};
