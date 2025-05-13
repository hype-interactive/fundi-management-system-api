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
        Schema::create('sms_campaign_filters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_campaign_id')->constrained()->cascadeOnDelete();
            $table->enum('filter_type', ['region', 'district', 'ward', 'ranking', 'status']);
            $table->string('filter_value');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_campaign_filters');
    }
};
