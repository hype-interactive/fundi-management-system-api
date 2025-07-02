<div wire:ignore.self class="modal fade" id="campaignComposeModal" tabindex="-1"
    aria-labelledby="campaignComposeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">
                    <i class="fa-solid fa-envelope me-2"></i>
                    {{ $editingCampaign ? 'Edit Campaign' : 'New SMS Campaign' }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form wire:submit.prevent="saveCampaign">
                    <!-- Campaign Title -->
                    <div class="mb-3">
                        <label class="form-label">Campaign Title</label>
                        <input wire:model="campaignTitle" type="text"
                            class="form-control @error('campaignTitle') is-invalid @enderror"
                            placeholder="Enter campaign title">
                        @error('campaignTitle')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Message Content -->
                    <div class="mb-3">
                        <label class="form-label d-flex justify-content-between">
                            <span>Message Content</span>
                            <small class="text-muted">{{ strlen($campaignMessage) }}/1000 characters</small>
                        </label>

                        <!-- Template Selector -->
                        @if (count($templates) > 0)
                            <div class="mb-2">
                                <select wire:model="selectedTemplate" wire:change="applyTemplate($event.target.value)"
                                    class="form-control">
                                    <option value="">Choose a template...</option>
                                    @foreach ($templates as $template)
                                        <option value="{{ $template->id }}">{{ $template->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <textarea wire:model="campaignMessage" class="form-control @error('campaignMessage') is-invalid @enderror"
                            rows="4" maxlength="1000" placeholder="Type your message here..."></textarea>
                        @error('campaignMessage')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Recipients Section -->
                    <div class="mb-3">
                        <label class="form-label">Recipients</label>
                        <div class="row">
                            <div class="col-md-6">
                                <select wire:model.live="recipientType" class="form-control">
                                    <option value="both">All (Operators + Workers)</option>
                                    <option value="operators">Operators Only</option>
                                    <option value="fundis">Workers Only</option>
                                    <option value="custom">Custom Selection</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="recipient-count-badge">
                                    <span class="badge bg-info fs-6">
                                        <i class="fa-solid fa-users me-1"></i>
                                        {{ $recipientCount }} recipients
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Location Filters -->
                    {{-- @if ($recipientType !== 'custom')
                        <div class="mb-3">
                            <label class="form-label">Filter by Location (Optional)</label>
                            <div class="row g-2">
                                @if (isRoot())
                                    <div class="col-md-4">
                                        <div class="select">
                                            <select wire:model.live="selectedRegion" class="form-control">
                                                <option value="">All Regions</option>
                                                @foreach ($regions as $region)
                                                    <option value="{{ $region->id }}">{{ $region->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <select wire:model.live="selectedDistrict" class="form-control"
                                            {{ !$selectedRegion ? 'disabled' : '' }}>
                                            <option value="">All Districts</option>
                                            @foreach ($districts as $district)
                                                <option value="{{ $district->id }}">{{ $district->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div class="col-md-4">
                                    <div class="select">
                                        <select wire:model.live="selectedWard" class="form-control"
                                            {{ !$selectedDistrict ? 'disabled' : '' }}>
                                            <option value="">All Wards</option>
                                            @foreach ($wards as $ward)
                                                <option value="{{ $ward->id }}">{{ $ward->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            @if ($selectedRegion || $selectedDistrict || $selectedWard)
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fa-solid fa-map-marker-alt me-1"></i>
                                        Selected:
                                        @if ($selectedWard)
                                            {{ $wards->find($selectedWard)?->name }},
                                        @endif
                                        @if ($selectedDistrict)
                                            {{ $districts->find($selectedDistrict)?->name }},
                                        @endif
                                        @if ($selectedRegion)
                                            {{ $regions->find($selectedRegion)?->name }}
                                        @endif
                                    </small>
                                </div>
                            @endif
                        </div>
                    @endif --}}

                    <!-- Custom Recipients -->
                    @if ($recipientType === 'custom')
                        <div class="mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Select Operators</label>
                                    <select wire:model.live="selectedUsers" multiple class="form-control"
                                        size="6">
                                        @foreach ($operators as $operator)
                                            <option value="{{ $operator->id }}">{{ $operator->full_name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Select Workers (Mafundi)</label>
                                    <select wire:model.live="selectedFundis" multiple class="form-control"
                                        size="6">
                                        @foreach ($workers as $worker)
                                            <option value="{{ $worker->id }}">{{ $worker->name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Scheduling -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input wire:model.live="sendNow" class="form-check-input" type="checkbox"
                                id="sendNowSwitch">
                            <label class="form-check-label" for="sendNowSwitch">
                                Send immediately
                            </label>
                        </div>

                        @if (!$sendNow)
                            <div class="mt-2">
                                <label class="form-label">Schedule Date & Time</label>
                                <input wire:model="scheduledAt" type="datetime-local"
                                    class="form-control @error('scheduledAt') is-invalid @enderror"
                                    min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}">
                                @error('scheduledAt')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            Cancel
                        </button>
                        <button type="button" class="btn btn-primary" wire:click="saveCampaign"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove>
                                <i class="fa-solid fa-{{ $sendNow ? 'paper-plane' : 'clock' }} me-1"></i>
                                {{ $sendNow ? 'Send Now' : 'Schedule' }}
                            </span>
                            <span wire:loading>
                                <i class="fa-solid fa-spinner fa-spin me-1"></i>
                                Processing...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
