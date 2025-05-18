<select wire:model="ward" id="inputWard" class="form-control">
    <option value="{{ null }}"> -- Ward -- </option>
    @foreach ($wards as $item)
        <option value="{{ $item->id}}">{{ $item->name}}</option>
    @endforeach
</select>
