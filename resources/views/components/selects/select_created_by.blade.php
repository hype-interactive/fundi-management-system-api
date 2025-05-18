<div class="form-group">
    <select wire:model.live="created_by" id="inputCreatedBy" class="form-select form-select-sm">
        <option value=""> -- Created By -- </option>
        @foreach ($creators as $creator)
            <option value="{{ $creator->id }}">{{ $creator->full_name }}</option>
        @endforeach
    </select>
</div>
