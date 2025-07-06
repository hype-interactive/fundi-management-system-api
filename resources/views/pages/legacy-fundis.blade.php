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
                        <a href="{{ route('fundis') }}" class="">
                            <i class="fa-solid fa-users mr-4"></i>
                            Current Fundis
                        </a>
                        <a class="cursor-pointer" type="button" data-toggle="modal" data-target="#importLegacyModal">
                            <i class="fa-solid fa-cloud-arrow-up mr-4"></i>
                            Import Legacy Data
                        </a>
                        <a class="cursor-pointer" type="button" data-toggle="modal" data-target="#matchingHelpModal">
                            <i class="fa-solid fa-question-circle mr-4"></i>
                            Matching Help
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
                <button type="button" class="close" data-dismiss="alert"></button>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert"></button>
            </div>
        @endif

        <!-- Legacy Data Overview -->
        <div class="legacy-overview mb-4">
            <div class="row">
                <div class="col-md-8">
                    <div class="overview-content">
                        {{-- <h4>
                            <i class="fa-solid fa-info-circle me-2 text-primary"></i>
                            Legacy Data Cross-Check System
                        </h4> --}}
                        <p class="text-muted mb-0">
                            This system helps you identify and manage legacy fundi records,
                            cross-reference them with current data, and resolve duplicates through intelligent matching.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="quick-actions">
                        <button class="btn btn-primary btn-sm me-2" data-toggle="modal" data-target="#importLegacyModal">
                            <i class="fa-solid fa-upload me-1"></i>
                            Import Legacy
                        </button>
                        <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#matchingHelpModal">
                            <i class="fa-solid fa-question me-1"></i>
                            Help
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @livewire('legacy-fundis-table')

        <!-- Import Legacy Data Modal -->
        <div class="modal fade" id="importLegacyModal" tabindex="-1" aria-hidden="true" aria-labelledby="importLegacyModalLabel">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fa-solid fa-cloud-arrow-up me-2"></i>
                            Import Legacy Data
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('legacy-fundis.import') }}" method="POST" enctype="multipart/form-data">
                        {{-- <form enctype="multipart/form-data"> --}}
                            @csrf
                            <div class="mb-3">
                                <label for="legacy_file" class="form-label">
                                    <i class="fa-solid fa-file-excel me-1"></i>
                                    Select Legacy Data File
                                </label>
                                <input type="file" class="form-control" id="legacy_file" name="legacy_file"
                                    accept=".csv,.xlsx,.xls" required>
                                <div class="form-text">
                                    Supported formats: CSV, Excel (.xlsx, .xls).
                                    File should contain columns: name, gender, primary_phone, ranking, etc.
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="auto_match" name="auto_match">
                                    <label class="form-check-label" for="auto_match">
                                        Run automatic matching after import
                                    </label>
                                </div>
                                <div class="form-text">
                                    This will automatically link legacy records with high confidence matches (90%+).
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <h6><i class="fa-solid fa-info-circle me-1"></i> Expected File Format:</h6>
                                <ul class="mb-0">
                                    <li><strong>name</strong> - Full name of the fundi</li>
                                    <li><strong>gender</strong> - male, female, or other</li>
                                    <li><strong>primary_phone</strong> - Primary phone number</li>
                                    <li><strong>transaction_phone</strong> - Transaction phone (optional)</li>
                                    <li><strong>nida_number</strong> - NIDA number (optional)</li>
                                    <li><strong>ranking</strong> - fundi_kuu or fundi_msaidizi</li>
                                    <li><strong>region, district, ward</strong> - Location information</li>
                                    <li><strong>status</strong> - active, inactive, or pending</li>
                                </ul>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" form="importForm">
                            <i class="fa-solid fa-upload me-1"></i>
                            Import Data
                        </button>
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
                            <i class="fa-solid fa-question-circle me-2"></i>
                            Legacy Data Matching Guide
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="help-content">
                            <h6><i class="fa-solid fa-search me-1"></i> How Matching Works</h6>
                            <p>The system uses multiple criteria to find potential matches:</p>

                            <div class="matching-criteria">
                                <div class="criteria-item">
                                    <span class="badge bg-success">95% Confidence</span>
                                    <strong>NIDA Number Match</strong>
                                    <p>Exact match of NIDA numbers provides the highest confidence.</p>
                                </div>

                                <div class="criteria-item">
                                    <span class="badge bg-warning">85% Confidence</span>
                                    <strong>Phone Number Match</strong>
                                    <p>Exact match of primary or transaction phone numbers.</p>
                                </div>

                                <div class="criteria-item">
                                    <span class="badge bg-info">70%+ Confidence</span>
                                    <strong>Name Similarity</strong>
                                    <p>Fuzzy matching of names using text similarity algorithms.</p>
                                </div>
                            </div>

                            <h6 class="mt-4"><i class="fa-solid fa-cogs me-1"></i> Match Status Types</h6>
                            <div class="status-explanations">
                                <div class="status-item matched">
                                    <span class="badge bg-success">Matched</span>
                                    <p>Legacy record has been linked to a current fundi record.</p>
                                </div>

                                <div class="status-item potential">
                                    <span class="badge bg-warning">Potential Matches</span>
                                    <p>System found one or more possible matches that need manual review.</p>
                                </div>

                                <div class="status-item unmatched">
                                    <span class="badge bg-danger">Unmatched</span>
                                    <p>No potential matches found - may be a unique legacy record.</p>
                                </div>
                            </div>

                            <h6 class="mt-4"><i class="fa-solid fa-magic me-1"></i> Auto-Matching</h6>
                            <p>
                                The auto-match feature automatically links legacy records with current fundis
                                when the confidence level is 90% or higher. This is typically triggered by
                                exact NIDA number matches.
                            </p>

                            <div class="alert alert-warning">
                                <h6><i class="fa-solid fa-exclamation-triangle me-1"></i> Important Notes</h6>
                                <ul class="mb-0">
                                    <li>Always review potential matches before linking</li>
                                    <li>Auto-matching cannot be undone automatically</li>
                                    <li>Manual unlinking is possible if mistakes are made</li>
                                    <li>Regular backups are recommended before bulk operations</li>
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
