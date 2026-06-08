<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ========================================
// QUEUE WORKER MANAGEMENT
// ========================================

/**
 * Run queue worker every minute
 */
Schedule::command('queue:work --queue=disbursements,default --stop-when-empty --max-time=50')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/queue-worker.log'));

/**
 * Restart queue workers daily at 3 AM
 */
Schedule::command('queue:restart')
    ->daily()
    ->at('03:00')
    ->appendOutputTo(storage_path('logs/queue-restart.log'));

/**
 * Clean up old failed jobs (older than 7 days)
 */
Schedule::command('queue:prune-failed --hours=168')
    ->daily()
    ->at('04:00');

// ========================================
// QUEUE MONITORING
// ========================================

/**
 * Monitor queue size every 5 minutes
 */
Schedule::call(function () {
    $queueSize = DB::table('jobs')->count();

    if ($queueSize > 100) {
        Log::warning('Queue size is large', [
            'size' => $queueSize,
            'timestamp' => now(),
        ]);

        // TODO: Send notification to admin
    }

    if ($queueSize > 0) {
        Log::info('Queue status', [
            'total_jobs' => $queueSize,
            'disbursement_jobs' => DB::table('jobs')
                ->where('queue', 'disbursements')
                ->count(),
        ]);
    }
})->everyFiveMinutes();

/**
 * Check for stuck jobs (processing for over 10 minutes)
 */
Schedule::call(function () {
    $stuckJobs = DB::table('jobs')
        ->where('reserved_at', '<', now()->subMinutes(10)->timestamp)
        ->whereNotNull('reserved_at')
        ->count();

    if ($stuckJobs > 0) {
        Log::error('Stuck jobs detected', [
            'count' => $stuckJobs,
            'timestamp' => now(),
        ]);
    }
})->everyTenMinutes();

// ========================================
// DISBURSEMENT MONITORING
// ========================================

/**
 * Check for pending disbursements older than 1 hour
 */
Schedule::call(function () {
    $pendingCount = DB::table('transactions')
        ->whereIn('status', ['pending', 'processing'])
        ->where('created_at', '<', now()->subHour())
        ->count();

    if ($pendingCount > 0) {
        Log::warning('Old pending disbursements found', [
            'count' => $pendingCount,
            'timestamp' => now(),
        ]);
    }
})->hourly();

// ========================================
// DATABASE MAINTENANCE
// ========================================

/**
 * Prune old telescope entries (if using Laravel Telescope)
 */
Schedule::command('telescope:prune --hours=168')
    ->daily()
    ->at('05:00');
