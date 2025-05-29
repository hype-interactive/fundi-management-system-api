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
        Schema::table('users', function (Blueprint $table) {
            // Remove old profile_photo column if exists
            if (Schema::hasColumn('users', 'profile_photo')) {
                $table->dropColumn('profile_photo');
            }

            // Add Cloudinary fields
            $table->string('profile_photo_url')->nullable()->after('status');
            $table->string('profile_photo_public_id')->nullable()->after('profile_photo_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['profile_photo_url', 'profile_photo_public_id']);
            // Add back the old column if needed
            $table->string('profile_photo')->nullable();
        });
    }
};
