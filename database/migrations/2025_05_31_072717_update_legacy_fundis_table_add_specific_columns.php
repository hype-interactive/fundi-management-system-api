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
        Schema::table('legacy_fundis', function (Blueprint $table) {
            $table->string('region', 100)->nullable()->after('status');
            $table->string('district', 100)->nullable()->after('region');
            $table->string('ward', 100)->nullable()->after('district');

            // Indexes for faster querying
            $table->index(['region', 'district', 'ward'], 'idx_legacy_fundis_location');
            $table->index(['status', 'ranking'], 'idx_legacy_fundis_status_ranking');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('legacy_fundis', function (Blueprint $table) {
            $table->dropIndex('idx_legacy_fundis_location');
            $table->dropIndex('idx_legacy_fundis_status_ranking');
            $table->dropColumn(['region', 'district', 'ward']);
        });
    }
};
