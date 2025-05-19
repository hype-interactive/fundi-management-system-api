<select wire:model.live="approval_status" id="inputApprovalStatus" class="form-select form-select-sm">
    <option value=""> -- Approval Status -- </option>
    @foreach ($approval_status_options as $key => $value)
        <option value="{{ $key }}">{{ $value }}</option>
    @endforeach
</select>
