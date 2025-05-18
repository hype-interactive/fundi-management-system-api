<div class="form-group">
    <select wire:model.live="ranking" id="inputRanking" class="form-select form-select-sm">
        <option value=""> -- Ranking -- </option>
        @foreach ($ranking_options as $key => $value)
            <option value="{{ $key }}">{{ $value }}</option>
        @endforeach
    </select>
</div>
