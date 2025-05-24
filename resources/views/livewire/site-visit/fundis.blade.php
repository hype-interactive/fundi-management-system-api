<div class="tab-pane fade show active">
    <div class="fundis-section">
        @if ($siteVisit->fundis->count() > 0)
            <div class="fundis-list">
                @foreach ($siteVisit->fundis as $fundi)
                    <div class="fundi-item">
                        <div class="fundi-avatar">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div class="fundi-info">
                            <h6 class="fundi-name">
                                <a href="{{ route('fundi', $fundi) }}" class="text-decoration-none">
                                    {{ $fundi->fname }} {{ $fundi->mname }} {{ $fundi->lname }}
                                </a>
                            </h6>
                            <div class="fundi-details">
                                @if ($fundi->phone)
                                    <span class="phone">
                                        <i class="fa-solid fa-phone me-1"></i>
                                        {{ $fundi->phone }}
                                    </span>
                                @endif
                                @if ($fundi->specialization)
                                    <span class="specialization">
                                        <i class="fa-solid fa-tools me-1"></i>
                                        {{ $fundi->specialization }}
                                    </span>
                                @endif
                            </div>
                            @if ($fundi->pivot->notes)
                                <div class="fundi-notes">
                                    <i class="fa-solid fa-sticky-note me-1 text-muted"></i>
                                    <span>{{ $fundi->pivot->notes }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <i class="fa-solid fa-users fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Fundis Associated</h5>
                <p class="text-muted">No fundis were associated with this site visit.</p>
            </div>
        @endif
    </div>
</div>
