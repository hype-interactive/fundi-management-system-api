<div class="sms-templates-wrapper">
    <!-- Template Actions & Filters -->
    <div class="template-actions mb-3 d-flex justify-content-between align-items-center flex-wrap gap-3 mt-2">
        <!-- Search -->
        <div class="search">
            <input wire:model.live.debounce.300ms="search" type="text" class="form-control form-control-sm"
                placeholder="Search templates...">
            <i class="fa-solid fa-magnifying-glass"></i>
        </div>

        <div class="d-flex gap-2">
            <button wire:click="refresh" class="btn btn-primary" type="button" data-toggle="modal"
                data-target="#smsTemplateModal">
                <i class="fa-solid fa-plus me-1"></i>
                New Template
            </button>
        </div>
    </div>

    <!-- Templates List -->
    <div class="templates-section">
        <!-- Desktop Table -->
        <div class="d-none d-md-block">
            <div class="custom-table">
                <table class="table">
                    <thead class="thead-light">
                        <tr>
                            <th>Template</th>
                            <th>Content Preview</th>
                            <th>Created By</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                            <tr wire:key="template-{{ $template->id }}">
                                <td>
                                    <div class="template-info">
                                        <h6 class="mb-0">{{ $template->title }}</h6>
                                    </div>
                                </td>
                                <td>
                                    <small class="text-muted">{{ Str::limit($template->content, 80) }}</small>
                                </td>
                                <td>
                                    @if ($template->creator)
                                        <span class="text-muted">{{ $template->creator->name }}</span>
                                    @else
                                        <span class="text-muted">System</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ $template->created_at->format('M d, Y') }}</small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button wire:click="selectTemplate({{ $template->id }})"
                                            class="btn btn-outline-success btn-sm" title="Use Template">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                        <button wire:click="editTemplate({{ $template->id }})"
                                            class="btn btn-outline-primary btn-sm" title="Edit">
                                            <i class="fa-solid fa-edit"></i>
                                        </button>
                                        <button wire:click="duplicateTemplate({{ $template->id }})"
                                            class="btn btn-outline-info btn-sm" title="Duplicate">
                                            <i class="fa-solid fa-copy"></i>
                                        </button>
                                        <button wire:click="deleteTemplate({{ $template->id }})"
                                            class="btn btn-outline-danger btn-sm" title="Delete"
                                            onclick="return confirm('Are you sure you want to delete this template?')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fa-solid fa-file-text me-2"></i>
                                    No templates found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile Cards -->
        <div class="mobile-cards d-md-none">
            @forelse($templates as $template)
                <div class="template-card mb-3" wire:key="mobile-{{ $template->id }}">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">{{ $template->title }}</h6>
                            <small class="text-muted">{{ $template->created_at->format('M d, Y') }}</small>
                        </div>
                        <div class="card-body">
                            <p class="card-text">{{ Str::limit($template->content, 120) }}</p>
                            <div class="template-meta">
                                <small class="text-muted d-block">
                                    <i class="fa-solid fa-user me-1"></i>
                                    Created by:
                                    @if ($template->creator)
                                        {{ $template->creator->name }}
                                    @else
                                        System
                                    @endif
                                </small>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-end gap-2 flex-wrap">
                            <button wire:click="selectTemplate({{ $template->id }})"
                                class="btn btn-outline-success btn-sm">
                                <i class="fa-solid fa-check me-1"></i>
                                Use
                            </button>
                            <button wire:click="editTemplate({{ $template->id }})"
                                class="btn btn-outline-primary btn-sm">
                                <i class="fa-solid fa-edit me-1"></i>
                                Edit
                            </button>
                            <button wire:click="duplicateTemplate({{ $template->id }})"
                                class="btn btn-outline-info btn-sm">
                                <i class="fa-solid fa-copy me-1"></i>
                                Copy
                            </button>
                            <button wire:click="deleteTemplate({{ $template->id }})"
                                class="btn btn-outline-danger btn-sm"
                                onclick="return confirm('Are you sure you want to delete this template?')">
                                <i class="fa-solid fa-trash me-1"></i>
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state text-center py-5">
                    <i class="fa-solid fa-file-text fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No templates found</h5>
                    <p class="text-muted">Create your first SMS template to get started.</p>
                    <button wire:click="openTemplateModal" class="btn btn-primary">
                        <i class="fa-solid fa-plus me-1"></i>
                        Create Template
                    </button>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="pagination-wrapper d-flex justify-content-center mt-3">
            {{ $templates->links() }}
        </div>
    </div>

    <!-- Template Modal -->
    @include('components.modals.sms-template-modal')
</div>
