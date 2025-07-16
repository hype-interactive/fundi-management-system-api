@extends('layouts.app')

@section('page')
    <div class="page legacy-fundis-wrapper">
        <div class="custom-header">
            <h2>
                <i class="fa-solid fa-database me-2"></i>
                Legacy Fundis Management
            </h2>
            <div class="actions">
                <div class="dropdown">
                    <i class="fa-solid fa-bars icon" data-toggle="dropdown" aria-expanded="false"></i>
                    <div class="dropdown-menu">
                        <a href="{{ route('fundis') }}" class="dropdown-item">
                            <i class="fa-solid fa-users me-2"></i>
                            Current Fundis
                        </a>
                        <a class="dropdown-item" data-toggle="modal" data-target="#importLegacyModal">
                            <i class="fa-solid fa-cloud-arrow-up me-2"></i>
                            Import Legacy Data
                        </a>
                        <a class="dropdown-item" data-toggle="modal" data-target="#matchingHelpModal">
                            <i class="fa-solid fa-question-circle me-2"></i>
                            Matching Help
                        </a>
                        <a href="{{ route('legacy-fundis.export-analysis') }}" class="dropdown-item">
                            <i class="fa-solid fa-download me-2"></i>
                            Export Analysis
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <!-- Legacy Data Overview -->
        <div class="legacy-overview mb-4">
            <div class="row">
                <div class="col-md-8">
                    <div class="overview-content">
                        <p class="text-muted mb-0">
                            This system helps you identify and manage legacy fundi records,
                            cross-reference them with current data, and resolve duplicates through intelligent matching.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="quick-actions">
                        <button class="btn btn-primary btn-sm mr-2" data-toggle="modal" data-target="#importLegacyModal">
                            <i class="fa-solid fa-upload mr-1"></i>
                            Import Legacy
                        </button>
                        <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#matchingHelpModal">
                            <i class="fa-solid fa-question mr-1"></i>
                            Help
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Table Component -->
        @livewire('legacy-fundis-table')

        <!-- Import Legacy Data Modal -->
        <div class="modal fade" id="importLegacyModal" tabindex="-1" aria-hidden="true" aria-labelledby="importLegacyModalLabel">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fa-solid fa-cloud-arrow-up mr-2"></i>
                            Import Legacy Data
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Import Progress Component -->
                        @livewire('import-progress')

                        <div class="alert alert-info mt-3">
                            <h6><i class="fa-solid fa-info-circle mr-1"></i> Expected File Format:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="mb-0">
                                        <li><strong>identifier</strong> - Phone number (column A)</li>
                                        <li><strong>full_name</strong> - Full name of the fundi (column B)</li>
                                        <li><strong>location</strong> - Region/District/Ward (column C)</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <strong>Sample CSV format:</strong><br>
                                        <code>
                                            identifier,full_name,location<br>
                                            255717588599,James Belushi,TANGA<br>
                                            255658032005,Kayage,ILALA
                                        </code>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <h6><i class="fa-solid fa-exclamation-triangle mr-1"></i> Important Notes:</h6>
                            <ul class="mb-0">
                                <li>Large files will be processed in the background</li>
                                <li>You can monitor progress in real-time</li>
                                <li>Don't close the browser during import</li>
                                <li>Duplicate records will be automatically skipped</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Matching Help Modal -->
        <div class="modal fade" id="matchingHelpModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fa-solid fa-question-circle mr-2"></i>
                            Legacy Data Matching Guide
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="help-content">
                            <h6><i class="fa-solid fa-search mr-1"></i> How Matching Works</h6>
                            <p>The system uses multiple criteria to find potential matches with clear priority:</p>

                            <div class="matching-criteria">
                                <div class="criteria-item mb-3">
                                    <span class="badge badge-success mr-2">95% Confidence</span>
                                    <strong>NIDA Number Match</strong>
                                    <p class="mb-0">Exact match of NIDA numbers provides the highest confidence and enables auto-linking.</p>
                                </div>

                                <div class="criteria-item mb-3">
                                    <span class="badge badge-warning mr-2">85% Confidence</span>
                                    <strong>Phone Number Match</strong>
                                    <p class="mb-0">Exact match of primary or transaction phone numbers.</p>
                                </div>

                                <div class="criteria-item mb-3">
                                    <span class="badge badge-info mr-2">60-80% Confidence</span>
                                    <strong>Name Similarity</strong>
                                    <p class="mb-0">Fuzzy matching of names using text similarity algorithms, enhanced by location proximity.</p>
                                </div>
                            </div>

                            <h6 class="mt-4"><i class="fa-solid fa-cogs mr-1"></i> Match Status Types</h6>
                            <div class="status-explanations">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge badge-success mr-2">Matched</span>
                                    <p class="mb-0">Legacy record has been linked to a current fundi record.</p>
                                </div>

                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge badge-warning mr-2">Potential Matches</span>
                                    <p class="mb-0">System found one or more possible matches that need manual review.</p>
                                </div>

                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge badge-danger mr-2">No Matches</span>
                                    <p class="mb-0">No potential matches found - may be a unique legacy record.</p>
                                </div>
                            </div>

                            <h6 class="mt-4"><i class="fa-solid fa-magic mr-1"></i> Auto-Matching</h6>
                            <p>
                                The auto-match feature automatically links legacy records with current fundis
                                when the confidence level is 90% or higher (typically NIDA number matches).
                                This can be enabled during import or run manually afterwards.
                            </p>

                            <h6 class="mt-4"><i class="fa-solid fa-cog mr-1"></i> Performance Features</h6>
                            <ul>
                                <li><strong>Background Processing:</strong> Large imports are processed in queues</li>
                                <li><strong>Caching:</strong> Match counts are cached for faster page loads</li>
                                <li><strong>Batch Operations:</strong> Bulk linking and processing capabilities</li>
                                <li><strong>Real-time Progress:</strong> Live updates during import operations</li>
                            </ul>

                            <div class="alert alert-warning mt-3">
                                <h6><i class="fa-solid fa-exclamation-triangle mr-1"></i> Best Practices</h6>
                                <ul class="mb-0">
                                    <li>Always review potential matches before linking</li>
                                    <li>Use bulk auto-match for high-confidence matches only</li>
                                    <li>Manual unlinking is possible if mistakes are made</li>
                                    <li>Export analysis data for external review if needed</li>
                                    <li>Run imports during off-peak hours for large files</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">Got it!</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-close import modal when import starts
    Livewire.on('importStarted', () => {
        $('#importLegacyModal').modal('hide');
    });

    // Refresh table when import completes
    Livewire.on('importCompleted', () => {
        Livewire.dispatch('refreshTable');
    });
});
</script>
@endpush
