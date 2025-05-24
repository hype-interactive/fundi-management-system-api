<div class="tab-pane fade show active">
    <div class="photos-section">
        @if ($siteVisit->photos->count() > 0)
            <div class="photos-grid">
                @foreach ($siteVisit->photos as $photo)
                    <div class="photo-item">
                        <img src="{{ Storage::url($photo->photo_path) }}" alt="Site visit photo" class="img-fluid rounded"
                            data-bs-toggle="modal" data-bs-target="#photoModal{{ $photo->id }}">
                        <div class="photo-overlay">
                            <i class="fa-solid fa-search-plus"></i>
                        </div>
                    </div>

                    <!-- Photo Modal -->
                    <div class="modal fade" id="photoModal{{ $photo->id }}" tabindex="-1" wire:ignore>
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Site Visit Photo</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <img src="{{ Storage::url($photo->photo_path) }}" alt="Site visit photo"
                                        class="img-fluid">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <i class="fa-solid fa-camera fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Photos Available</h5>
                <p class="text-muted">No photos were uploaded for this site visit.</p>
            </div>
        @endif
    </div>
</div>
