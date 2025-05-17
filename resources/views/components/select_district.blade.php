<select wire:model="district" id="inputDistrict" class="form-control">
    <option> -- District -- </option>
    @foreach ($districts as $item)
        <option value="{{ $item->id}}">{{ $item->name}}</option>
    @endforeach
</select>
