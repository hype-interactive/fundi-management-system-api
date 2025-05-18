<div class="form-group">
    <select wire:model.live="gender" id="inputGender" class="form-select form-select-sm">
        <option value=""> -- Gender -- </option>
        @foreach ($gender_options as $key => $value)
            <option value="{{ $key }}">{{ $value }}</option>
        @endforeach
    </select>
</div>
