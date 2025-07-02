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
        Schema::table('sms_campaigns', function (Blueprint $table) {
            // Change 'status' column to string temporarily
            $table->string('status')->change();
        });

        // Then modify the enum
        Schema::table('sms_campaigns', function (Blueprint $table) {
            $table->enum('status', ['draft', 'scheduled', 'processing', 'completed', 'cancelled', 'failed', 'partially_completed'])->default('draft')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_campaigns', function (Blueprint $table) {
            $table->string('status')->change();
        });

        Schema::table('sms_campaigns', function (Blueprint $table) {
            $table->enum('status', ['draft', 'scheduled', 'processing', 'completed', 'cancelled'])->default('draft')->change();
        });
    }
};
