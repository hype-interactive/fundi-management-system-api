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
        Schema::create('disbursement_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_allocation_id')->constrained();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('total_amount', 12, 2);
            $table->timestamp('disbursement_date');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disbursement_batches');
    }
};
