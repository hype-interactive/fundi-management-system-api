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
        Schema::create('fundis', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id', 20)->unique();
            $table->string('name');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('nida_number', 20)->unique()->nullable();
            $table->string('primary_phone', 20);
            $table->string('transaction_phone', 20);
            $table->foreignId('ward_id')->constrained();
            $table->enum('ranking', ['fundi_kuu', 'fundi_msaidizi']);
            $table->string('profile_photo')->nullable();
            $table->enum('status', ['active', 'inactive', 'pending'])->default('pending');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->boolean('legacy_match')->default(false);
            $table->string('legacy_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fundis');
    }
};
