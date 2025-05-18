<select wire:model.live="district" id="inputDistrict" class="form-control">
    <option value=""> -- District -- </option>
    @foreach ($districts as $item)
        <option value="{{ $item->id}}">{{ $item->name}}</option>
    @endforeach
</select>
