<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fundis', function (Blueprint $table) {
            $table->index('status');
            $table->index('ward_id');
        });

        Schema::table('site_visits', function (Blueprint $table) {
            $table->index('status');
            $table->index('operator_id');
            $table->index('visit_date');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('disbursement_batches', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('sms_messages', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('fundis', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['ward_id']);
        });

        Schema::table('site_visits', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['operator_id']);
            $table->dropIndex(['visit_date']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('disbursement_batches', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('sms_messages', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};
