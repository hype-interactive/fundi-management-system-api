<div class="tab-pane fade show active">
    <div class="photos-section">
        @if ($siteVisit->photos->count() > 0)
            <div class="photos-grid">
                @foreach ($siteVisit->photos as $photo)
                    @php
                        // Determine the correct photo URL based on storage type
                        $photoUrl = '';
                        $isCloudinary = false;

                        if (!empty($photo->photo_url)) {
                            // Cloudinary photo - use direct URL
                            $photoUrl = $photo->photo_url;
                            $isCloudinary = true;
                        } elseif (!empty($photo->photo_path)) {
                            // Legacy photo - use Laravel Storage
                            $photoUrl = Storage::url($photo->photo_path);
                            $isCloudinary = false;
                        } else {
                            // Fallback - no valid photo URL
                            $photoUrl = asset('images/no_image.jpg'); // Add a placeholder image
                        }
                    @endphp
                    <div class="photo-item">
                        <img src="{{ $photoUrl }}" alt="Site visit photo" class="img-fluid rounded photo-thumbnail"
                            data-toggle="modal" data-target="#photoModal{{ $photo->id }}" loading="lazy"
                            onerror="this.onerror=null; this.src='{{ asset('images/no_image.jpg') }}';">

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
                                    <button type="button" class="btn-close" data-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <div class="photo-container">
                                        <img src="{{ $photoUrl }}" alt="Site visit photo"
                                            class="img-fluid modal-photo" loading="lazy"
                                            onerror="this.onerror=null; this.src='{{ asset('images/no_image.jpg') }}';">
                                    </div>
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
