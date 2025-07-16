<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Jobs\ProcessLegacyImportJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ImportProgress extends Component
{
    use WithFileUploads;

    public $file;
    public $autoMatch = false;
    public $isUploading = false;
    public $isProcessing = false;
    public $currentJobId = null;
    public $progress = [
        'percentage' => 0,
        'message' => '',
        'status' => 'idle', // idle, uploading, processing, completed, failed
        'stats' => []
    ];

    protected $rules = [
        'file' => 'required|file|mimes:csv,xlsx,xls|max:20480', // 20MB
    ];

    protected $messages = [
        'file.required' => 'Please select a file to import.',
        'file.mimes' => 'File must be a CSV or Excel file (.csv, .xlsx, .xls).',
        'file.max' => 'File size must not exceed 20MB.',
    ];

    protected $listeners = [
        'checkProgress' => 'checkImportProgress'
    ];

    public function mount()
    {
        // Check if there's an ongoing import for this user
        $this->checkForOngoingImport();
    }

    public function render()
    {
        return view('livewire.import-progress');
    }

    /**
     * Start the import process
     */
    public function startImport()
    {
        $this->validate();

        try {
            $this->isUploading = true;
            $this->updateProgress(0, 'Uploading file...', 'uploading');

            // Store the file
            $filePath = $this->file->store('imports', 'local');

            // Generate unique job ID
            $jobId = 'import_' . uniqid() . '_' . Auth::id();
            $this->currentJobId = $jobId;

            // Dispatch the job
            ProcessLegacyImportJob::dispatch(
                $filePath,
                Auth::id(),
                $this->autoMatch,
                $jobId
            );

            $this->isUploading = false;
            $this->isProcessing = true;
            $this->updateProgress(5, 'File uploaded. Starting import...', 'processing');

            // Store the job ID in session for persistence
            session(['current_import_job' => $jobId]);

            // Start polling for progress
            $this->dispatch('startPolling');

        } catch (\Exception $e) {
            $this->isUploading = false;
            $this->updateProgress(0, 'Upload failed: ' . $e->getMessage(), 'failed');
        }
    }

    /**
     * Check import progress
     */
    public function checkImportProgress()
    {
        if (!$this->currentJobId) {
            return;
        }

        $progress = cache()->get("import_progress_{$this->currentJobId}");

        if ($progress) {
            $this->progress = $progress;

            if ($progress['status'] === 'completed') {
                $this->isProcessing = false;
                $this->dispatch('stopPolling');
                $this->dispatch('importCompleted', $progress['stats']);
                session()->forget('current_import_job');

                // Refresh the main table
                $this->dispatch('refreshTable')->to('legacy-fundis-table');

            } elseif ($progress['status'] === 'failed') {
                $this->isProcessing = false;
                $this->dispatch('stopPolling');
                session()->forget('current_import_job');
            }
        }
    }

    /**
     * Check for ongoing import when component loads
     */
    public function checkForOngoingImport()
    {
        $jobId = session('current_import_job');

        if ($jobId) {
            $progress = cache()->get("import_progress_{$jobId}");

            if ($progress && in_array($progress['status'], ['processing', 'uploading'])) {
                $this->currentJobId = $jobId;
                $this->isProcessing = true;
                $this->progress = $progress;
                $this->dispatch('startPolling');
            } else {
                // Clear stale session data
                session()->forget('current_import_job');
            }
        }
    }

    /**
     * Cancel current import
     */
    public function cancelImport()
    {
        if ($this->currentJobId) {
            // Try to cancel the job (this depends on your queue implementation)
            // For database queue, you could mark it as failed

            $this->isProcessing = false;
            $this->updateProgress(0, 'Import cancelled by user.', 'failed');
            $this->dispatch('stopPolling');
            session()->forget('current_import_job');

            cache()->forget("import_progress_{$this->currentJobId}");
            $this->currentJobId = null;
        }
    }

    /**
     * Reset the import form
     */
    public function resetImport()
    {
        $this->reset([
            'file',
            'autoMatch',
            'isUploading',
            'isProcessing',
            'currentJobId'
        ]);

        $this->progress = [
            'percentage' => 0,
            'message' => '',
            'status' => 'idle',
            'stats' => []
        ];

        session()->forget('current_import_job');
    }

    /**
     * Update progress display
     */
    private function updateProgress(int $percentage, string $message, string $status, array $stats = [])
    {
        $this->progress = [
            'percentage' => $percentage,
            'message' => $message,
            'status' => $status,
            'stats' => $stats,
        ];
    }

    /**
     * Get progress bar color class
     */
    public function getProgressBarClass()
    {
        return match($this->progress['status']) {
            'completed' => 'bg-success',
            'failed' => 'bg-danger',
            'processing', 'uploading' => 'bg-primary',
            default => 'bg-secondary'
        };
    }

    /**
     * Get status icon
     */
    public function getStatusIcon()
    {
        return match($this->progress['status']) {
            'completed' => 'fa-check-circle text-success',
            'failed' => 'fa-times-circle text-danger',
            'processing' => 'fa-spinner fa-spin text-primary',
            'uploading' => 'fa-cloud-upload-alt text-info',
            default => 'fa-clock text-muted'
        };
    }
}
