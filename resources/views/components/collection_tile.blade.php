<div class="collection-tile-wrapper">
    <div class="tile">
        <div class="row">
            <div class="col-sm-8">
                <div class="main-text">
                    {{ number_format($amount) }}
                    @if ($difference == 0)
                        <i class="fa-solid fa-minus neutral"></i>
                    @elseif ($status)
                        <i class="fa-solid fa-arrow-up-long up"></i>
                    @else
                        <i class="fa-solid fa-arrow-down-long down"></i>
                    @endif
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-semi-bold">{{ $type }}</div>
                <div class="separator"></div>
                @if ($difference == 0)
                    <div class="text-muted text-semi-bold">
                        {{ number_format($difference) }}
                    </div>
                @elseif ($status)
                    <div class="text-success text-semi-bold">
                        {{ number_format($difference) }}
                    </div>
                @else
                    <div class="text-fail text-semi-bold">
                        {{ number_format($difference) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
