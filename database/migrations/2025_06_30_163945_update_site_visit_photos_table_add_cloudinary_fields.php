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
        Schema::table('site_visit_photos', function (Blueprint $table) {
            // Remove old photo column if exists
            if (Schema::hasColumn('site_visit_photos', 'photo_path')) {
                $table->dropColumn('photo_path');
            }

            // Add Cloudinary fields
            $table->string('photo_url')->nullable()->after('site_visit_id');
            $table->string('photo_public_id')->nullable()->after('photo_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_visit_photos', function (Blueprint $table) {
            $table->dropColumn(['photo_url', 'photo_public_id']);

            // Add back the old column if needed
            $table->string('photo_path')->nullable()->after('site_visit_id');
        });
    }
};
