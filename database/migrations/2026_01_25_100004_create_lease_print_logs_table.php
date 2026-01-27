<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lease_print_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('printed_at');
            $table->string('workstation')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->integer('copies_printed')->default(1);
            $table->string('print_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['lease_id', 'printed_at']);
            $table->index(['user_id', 'printed_at']);
            $table->index('printed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lease_print_logs');
    }
};
