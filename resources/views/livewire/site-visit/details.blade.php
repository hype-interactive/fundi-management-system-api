<div class="tab-pane fade show active">
    <div class="details-form">
        <div class="form-section">
            <h5 class="section-title">
                <i class="fa-solid fa-info-circle mr-2"></i>
                Basic Information
            </h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Site Name</label>
                        <div class="form-display">{{ $siteVisit->site_name }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Visit Date</label>
                        <div class="form-display">{{ $siteVisit->visit_date->format('F j, Y') }}</div>
                    </div>
                </div>
            </div>

            @if ($siteVisit->site_description)
                <div class="form-group">
                    <label>Description</label>
                    <div class="form-display">{{ $siteVisit->site_description }}</div>
                </div>
            @endif
        </div>

        <div class="form-section">
            <h5 class="section-title">
                <i class="fa-solid fa-user mr-2"></i>
                Operator Information
            </h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Operator Name</label>
                        <div class="form-display">{{ $siteVisit->operator->full_name ?? 'N/A' }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Contact</label>
                        <div class="form-display">{{ $siteVisit->operator->phone ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h5 class="section-title">
                <i class="fa-solid fa-map-marker-alt mr-2"></i>
                Location Information
            </h5>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Ward</label>
                        <div class="form-display">{{ $siteVisit->ward->name ?? 'N/A' }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>District</label>
                        <div class="form-display">{{ $siteVisit->ward->district->name ?? 'N/A' }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Region</label>
                        <div class="form-display">{{ $siteVisit->ward->district->region->name ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>

            @if ($siteVisit->gps_latitude && $siteVisit->gps_longitude)
                <div class="form-group">
                    <label>GPS Coordinates</label>
                    <div class="form-display gps-coords">
                        <i class="fa-solid fa-location-dot text-success me-2"></i>
                        <span>{{ $siteVisit->gps_latitude }}, {{ $siteVisit->gps_longitude }}</span>
                        <a href="https://maps.google.com/?q={{ $siteVisit->gps_latitude }},{{ $siteVisit->gps_longitude }}"
                            target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                            <i class="fa-solid fa-external-link-alt me-1"></i>
                            View on Map
                        </a>
                    </div>
                </div>
            @endif
        </div>

        @if ($siteVisit->status === 'approved' && $siteVisit->approver)
            <div class="form-section">
                <h5 class="section-title">
                    <i class="fa-solid fa-check-circle mr-2 text-success"></i>
                    Approval Information
                </h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Approved By</label>
                            <div class="form-display">{{ $siteVisit->approver->full_name }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Approved At</label>
                            <div class="form-display">{{ $siteVisit->approved_at->format('F j, Y g:i A') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
