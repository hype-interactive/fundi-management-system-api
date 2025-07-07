<div class="import-progress-wrapper">
    <!-- Import Form -->
    @if (!$isProcessing && $progress['status'] !== 'completed')
        <div class="import-form">
            <form wire:submit.prevent="startImport">
                <div class="form-group">
                    <label for="file" class="form-label">
                        <i class="fa-solid fa-file-excel mr-1"></i>
                        Select Legacy Data File
                    </label>
                    <input type="file" wire:model="file" class="form-control @error('file') is-invalid @enderror"
                        id="file" accept=".csv,.xlsx,.xls">
                    @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                        Supported formats: CSV, Excel (.xlsx, .xls). Maximum size: 20MB.
                        <br>Expected columns: identifier (phone), full_name, location
                    </small>
                </div>

                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" wire:model="autoMatch" id="autoMatch">
                        <label class="form-check-label" for="autoMatch">
                            Run automatic matching after import
                        </label>
                    </div>
                    <small class="form-text text-muted">
                        This will automatically link legacy records with high confidence matches (90%+).
                    </small>
                </div>

                <div class="d-flex">
                    <button type="submit" class="btn btn-primary mr-2" {{ $isUploading || !$file ? 'disabled' : '' }}>
                        <i class="fa-solid fa-upload mr-1"></i>
                        @if ($isUploading)
                            <span class="spinner-border spinner-border-sm mr-1" role="status"></span>
                            Uploading...
                        @else
                            Start Import
                        @endif
                    </button>

                    @if ($isProcessing)
                        <button type="button" wire:click="cancelImport" class="btn btn-outline-danger">
                            <i class="fa-solid fa-times mr-1"></i>
                            Cancel
                        </button>
                    @endif
                </div>
            </form>
        </div>
    @endif

    <!-- Progress Display -->
    @if ($isProcessing || $progress['status'] === 'completed' || $progress['status'] === 'failed')
        <div class="progress-display mt-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fa-solid {{ $this->getStatusIcon() }} mr-2"></i>
                        Import Progress
                    </h6>
                    @if ($progress['status'] === 'completed' || $progress['status'] === 'failed')
                        <button wire:click="resetImport" class="btn btn-sm btn-outline-secondary">
                            <i class="fa-solid fa-redo mr-1"></i>
                            New Import
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    <!-- Progress Bar -->
                    <div class="progress mb-3" style="height: 20px;">
                        <div class="progress-bar {{ $this->getProgressBarClass() }} @if ($isProcessing) progress-bar-striped progress-bar-animated @endif"
                            role="progressbar" style="width: {{ $progress['percentage'] }}%"
                            aria-valuenow="{{ $progress['percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                            {{ $progress['percentage'] }}%
                        </div>
                    </div>

                    <!-- Status Message -->
                    <div class="status-message">
                        <p class="mb-2">{{ $progress['message'] }}</p>

                        @if ($progress['status'] === 'processing')
                            <small class="text-muted">
                                <i class="fa-solid fa-info-circle mr-1"></i>
                                This may take a few minutes for large files. Please don't close this page.
                            </small>
                        @endif
                    </div>

                    <!-- Import Statistics -->
                    @if (!empty($progress['stats']))
                        <div class="import-stats mt-3">
                            <div class="row text-center">
                                <div class="col">
                                    <div class="stat-item">
                                        <h5 class="text-success mb-1">{{ $progress['stats']['imported'] ?? 0 }}</h5>
                                        <small class="text-muted">Imported</small>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="stat-item">
                                        <h5 class="text-warning mb-1">{{ $progress['stats']['skipped'] ?? 0 }}</h5>
                                        <small class="text-muted">Skipped</small>
                                    </div>
                                </div>
                                @if (isset($progress['stats']['matched']))
                                    <div class="col">
                                        <div class="stat-item">
                                            <h5 class="text-info mb-1">{{ $progress['stats']['matched'] }}</h5>
                                            <small class="text-muted">Auto-matched</small>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Error Display -->
                    @if ($progress['status'] === 'failed')
                        <div class="alert alert-danger mt-3">
                            <i class="fa-solid fa-exclamation-triangle mr-2"></i>
                            <strong>Import Failed:</strong> {{ $progress['message'] }}
                        </div>
                    @endif

                    <!-- Success Actions -->
                    @if ($progress['status'] === 'completed')
                        <div class="alert alert-success mt-3">
                            <i class="fa-solid fa-check-circle mr-2"></i>
                            <strong>Import Completed Successfully!</strong>
                        </div>
                        <div class="d-flex mt-3">
                            <button wire:click="$dispatch('refreshTable')" class="btn btn-outline-primary btn-sm mr-2">
                                <i class="fa-solid fa-refresh mr-1"></i>
                                Refresh Table
                            </button>
                            <a href="{{ route('legacy-fundis.index') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fa-solid fa-table mr-1"></i>
                                View Data
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- File Upload Progress -->
    <div wire:loading wire:target="file" class="text-center mt-3">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Processing file...</p>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
            let pollInterval;

            // Start polling for progress updates
            Livewire.on('startPolling', () => {
                pollInterval = setInterval(() => {
                    @this.checkImportProgress();
                }, 2000); // Check every 2 seconds
            });

            // Stop polling
            Livewire.on('stopPolling', () => {
                if (pollInterval) {
                    clearInterval(pollInterval);
                    pollInterval = null;
                }
            });

            // Show success message on completion
            Livewire.on('importCompleted', (stats) => {
                console.log('Import completed with stats:', stats);
                // Dispatch event to close modal and refresh table
                Livewire.dispatch('importStarted');
            });

            // Clean up on page unload
            window.addEventListener('beforeunload', () => {
                if (pollInterval) {
                    clearInterval(pollInterval);
                }
            });
        });
    </script>
@endpush
