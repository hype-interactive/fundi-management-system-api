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
        Schema::create('legacy_fundis', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('nida_number', 20)->unique()->nullable();
            $table->string('primary_phone', 20);
            $table->string('transaction_phone', 20)->nullable();
            $table->enum('ranking', ['fundi_kuu', 'fundi_msaidizi']);
            $table->string('profile_photo')->nullable();
            $table->enum('status', ['active', 'inactive', 'pending'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legacy_fundis');
    }
};
