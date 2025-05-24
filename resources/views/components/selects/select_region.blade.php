<select wire:model.live="region" id="inputRegion" class="form-control">
    <option value=""> -- Region -- </option>
    @foreach ($regions as $item)
        <option value="{{ $item->id}}">{{ $item->name}}</option>
    @endforeach
</select>
