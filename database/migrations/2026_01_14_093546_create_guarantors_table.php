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
        Schema::create('guarantors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_id')->constrained()->onDelete('cascade');
            $table->string('name', 200); // Guarantor full name
            $table->string('id_number', 20); // National ID
            $table->string('phone', 20); // Phone number
            $table->string('email', 100)->nullable(); // Email address (optional)
            $table->string('relationship', 50); // Relationship to tenant (e.g., 'Parent', 'Spouse', 'Employer')
            $table->decimal('guarantee_amount', 12, 2)->nullable(); // Amount guaranteed (optional, defaults to deposit)
            $table->boolean('signed')->default(false); // Has guarantor signed
            $table->timestamp('signed_at')->nullable(); // When signed
            $table->text('notes')->nullable(); // Additional notes
            $table->timestamps();

            // Indexes
            $table->index('lease_id');
            $table->index('id_number'); // For searching by ID
            $table->index('signed'); // For finding unsigned guarantors
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guarantors');
    }
};
