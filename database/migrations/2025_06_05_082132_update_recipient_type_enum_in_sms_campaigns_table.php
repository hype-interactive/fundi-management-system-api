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
            // Change 'recipient_type' column to string temporarily
            $table->string('recipient_type')->change();
        });

        // Then modify the enum
        Schema::table('sms_campaigns', function (Blueprint $table) {
            $table->enum('recipient_type', ['fundis', 'operators', 'both', 'custom'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_campaigns', function (Blueprint $table) {
            $table->string('recipient_type')->change();
        });

        Schema::table('sms_campaigns', function (Blueprint $table) {
            $table->enum('recipient_type', ['fundis', 'operators', 'both'])->change();
        });
    }
};
