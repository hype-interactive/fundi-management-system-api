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
        Schema::table('sms_campaign_filters', function (Blueprint $table) {
            $table->string('filter_type')->change();
        });

        // Then modify the enum
        Schema::table('sms_campaign_filters', function (Blueprint $table) {
            $table->enum('filter_type', ['region', 'district', 'ward', 'ranking', 'status', 'user'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_campaign_filters', function (Blueprint $table) {
            $table->string('filter_type')->change();
        });

        Schema::table('sms_campaign_filters', function (Blueprint $table) {
            $table->enum('filter_type', ['region', 'district', 'ward', 'ranking', 'status'])->change();
        });
    }
};
