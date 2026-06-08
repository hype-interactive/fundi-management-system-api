<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fundis', function (Blueprint $table) {
            // Index for rejected_by filtering (MISSING - good for audit trails)
            $table->index('rejected_by', 'idx_rejected_by');

            // Index for unique_id searches (if not already unique indexed)
            // This helps with show() endpoint lookups
            if (! $this->indexExists('fundis', 'unique_id')) {
                $table->index('unique_id', 'idx_unique_id');
            }

            // Index for soft deletes (MISSING - important for queries)
            $table->index('deleted_at', 'idx_deleted_at');

            // Composite index for status + approved_at (MISSING)
            // This is different from existing idx_ward_approval
            // Helps queries like: WHERE status='active' AND approved_at IS NOT NULL
            $table->index(['status', 'approved_at'], 'idx_status_approved_at');

            // Composite index for rejected_at + rejected_by (MISSING)
            // Helps with rejection queries and reporting
            $table->index(['rejected_at', 'rejected_by'], 'idx_rejected_info');
        });

        // Add full-text index for name searching (MySQL 5.7+ / MariaDB 10.0.5+)
        // This will SIGNIFICANTLY improve LIKE '%name%' searches
        // Only run if using MySQL/MariaDB
        if ($this->isMySQLOrMariaDB()) {
            DB::statement('CREATE FULLTEXT INDEX idx_fulltext_names ON fundis(fname, mname, lname)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fundis', function (Blueprint $table) {
            $table->dropIndex('idx_rejected_by');

            if ($this->indexExists('fundis', 'idx_unique_id')) {
                $table->dropIndex('idx_unique_id');
            }

            $table->dropIndex('idx_deleted_at');
            $table->dropIndex('idx_status_approved_at');
            $table->dropIndex('idx_rejected_info');
        });

        // Drop full-text index if it exists
        if ($this->isMySQLOrMariaDB()) {
            try {
                DB::statement('DROP INDEX idx_fulltext_names ON fundis');
            } catch (\Exception $e) {
                // Index might not exist, ignore
            }
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $indexes = DB::select(
                "SHOW INDEXES FROM {$table} WHERE Key_name = ?",
                [$indexName]
            );

            return ! empty($indexes);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if database is MySQL or MariaDB
     */
    private function isMySQLOrMariaDB(): bool
    {
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver");

        return in_array($connection, ['mysql', 'mariadb']);
    }
};
