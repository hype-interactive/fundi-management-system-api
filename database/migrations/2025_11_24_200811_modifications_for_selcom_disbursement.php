<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update transactions table to support disbursements
        Schema::table('transactions', function (Blueprint $table) {
            // Add indexes only if they don't exist
            if (!$this->indexExists('transactions', 'transactions_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('transactions', 'transactions_disbursement_batch_id_index')) {
                $table->index('disbursement_batch_id');
            }
            if (!$this->indexExists('transactions', 'transactions_disbursement_batch_id_status_index')) {
                $table->index(['disbursement_batch_id', 'status']);
            }

            // Add columns only if they don't exist
            if (!Schema::hasColumn('transactions', 'retry_count')) {
                $table->unsignedTinyInteger('retry_count')->default(0)->after('status');
            }
            if (!Schema::hasColumn('transactions', 'last_retry_at')) {
                $table->timestamp('last_retry_at')->nullable()->after('retry_count');
            }
        });

        // Update disbursement_batches table
        Schema::table('disbursement_batches', function (Blueprint $table) {
            if (!$this->indexExists('disbursement_batches', 'disbursement_batches_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('disbursement_batches', 'disbursement_batches_budget_allocation_id_index')) {
                $table->index('budget_allocation_id');
            }
            if (!$this->indexExists('disbursement_batches', 'disbursement_batches_budget_allocation_id_status_index')) {
                $table->index(['budget_allocation_id', 'status']);
            }
            if (!$this->indexExists('disbursement_batches', 'disbursement_batches_disbursement_date_index')) {
                $table->index('disbursement_date');
            }
        });

        // Update budget_allocations table
        Schema::table('budget_allocations', function (Blueprint $table) {
            if (!$this->indexExists('budget_allocations', 'budget_allocations_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('budget_allocations', 'budget_allocations_status_remaining_amount_index')) {
                $table->index(['status', 'remaining_amount']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if ($this->indexExists('transactions', 'transactions_status_index')) {
                $table->dropIndex('transactions_status_index');
            }
            if ($this->indexExists('transactions', 'transactions_disbursement_batch_id_index')) {
                $table->dropIndex('transactions_disbursement_batch_id_index');
            }
            if ($this->indexExists('transactions', 'transactions_disbursement_batch_id_status_index')) {
                $table->dropIndex('transactions_disbursement_batch_id_status_index');
            }

            if (Schema::hasColumn('transactions', 'retry_count')) {
                $table->dropColumn('retry_count');
            }
            if (Schema::hasColumn('transactions', 'last_retry_at')) {
                $table->dropColumn('last_retry_at');
            }
        });

        Schema::table('disbursement_batches', function (Blueprint $table) {
            if ($this->indexExists('disbursement_batches', 'disbursement_batches_status_index')) {
                $table->dropIndex('disbursement_batches_status_index');
            }
            if ($this->indexExists('disbursement_batches', 'disbursement_batches_budget_allocation_id_index')) {
                $table->dropIndex('disbursement_batches_budget_allocation_id_index');
            }
            if ($this->indexExists('disbursement_batches', 'disbursement_batches_budget_allocation_id_status_index')) {
                $table->dropIndex('disbursement_batches_budget_allocation_id_status_index');
            }
            if ($this->indexExists('disbursement_batches', 'disbursement_batches_disbursement_date_index')) {
                $table->dropIndex('disbursement_batches_disbursement_date_index');
            }
        });

        Schema::table('budget_allocations', function (Blueprint $table) {
            if ($this->indexExists('budget_allocations', 'budget_allocations_status_index')) {
                $table->dropIndex('budget_allocations_status_index');
            }
            if ($this->indexExists('budget_allocations', 'budget_allocations_status_remaining_amount_index')) {
                $table->dropIndex('budget_allocations_status_remaining_amount_index');
            }
        });
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]);
        return !empty($indexes);
    }
};
