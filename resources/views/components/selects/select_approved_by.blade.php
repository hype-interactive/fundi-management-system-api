<div class="form-group">
    <select wire:model.live="approved_by" id="inputApprovedBy" class="form-select form-select-sm">
        <option value=""> -- Approved By -- </option>
        @foreach ($approvers as $approver)
            <option value="{{ $approver->id }}">{{ $approver->full_name }}</option>
        @endforeach
    </select>
</div>
