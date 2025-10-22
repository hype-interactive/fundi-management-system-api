<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('fundis', function (Blueprint $table) {
            // Composite indexes for common query patterns
            $table->index(['approved_at', 'rejected_at'], 'idx_approval_status');
            $table->index(['ward_id', 'approved_at'], 'idx_ward_approval');
            $table->index(['created_by', 'created_at'], 'idx_creator_date');
            $table->index(['approved_by', 'approved_at'], 'idx_approver_date');
            
            // Individual indexes for filtering
            $table->index('gender', 'idx_gender');
            $table->index('ranking', 'idx_ranking');
            $table->index('legacy_match', 'idx_legacy_match');
            $table->index('status', 'idx_status');
            
            // Full-text or prefix indexes for search
            $table->index('primary_phone', 'idx_primary_phone');
            $table->index('transaction_phone', 'idx_transaction_phone');
            $table->index('nida_number', 'idx_nida_number');
            
            // Composite index for name searches (if not using full-text)
            $table->index(['fname', 'lname'], 'idx_name');
        });
    }

    public function down()
    {
        Schema::table('fundis', function (Blueprint $table) {
            $table->dropIndex('idx_approval_status');
            $table->dropIndex('idx_ward_approval');
            $table->dropIndex('idx_creator_date');
            $table->dropIndex('idx_approver_date');
            $table->dropIndex('idx_gender');
            $table->dropIndex('idx_ranking');
            $table->dropIndex('idx_legacy_match');
            $table->dropIndex('idx_status');
            $table->dropIndex('idx_primary_phone');
            $table->dropIndex('idx_transaction_phone');
            $table->dropIndex('idx_nida_number');
            $table->dropIndex('idx_name');
        });
    }
};