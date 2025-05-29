@props([
    'wire:model' => '',
    'existing' => null,
    'existingPublicId' => null,
    'label' => 'Upload Image',
    'required' => false,
    'preview' => true,
    'thumbnail' => true,
    'width' => 150,
    'height' => 150,
    'accept' => 'image/*',
    'maxSize' => '2MB',
    'removeMethod' => null
])

<div class="space-y-4">
    {{-- Label --}}
    @if($label)
        <label class="block text-sm font-medium text-gray-700">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    {{-- Existing Image Preview --}}
    @if($existing && $preview)
        <div class="relative inline-block">
            @if($thumbnail && $existingPublicId)
                {{-- Use Cloudinary component for existing images --}}
                <x-cloudinary::image
                    public-id="{{ $existingPublicId }}"
                    width="{{ $width }}"
                    height="{{ $height }}"
                    class="object-cover rounded-lg border border-gray-300"
                    alt="Current image"
                />
            @else
                {{-- Fallback for regular URLs --}}
                <img
                    src="{{ $existing }}"
                    alt="Current image"
                    class="object-cover rounded-lg border border-gray-300"
                    style="width: {{ $width }}px; height: {{ $height }}px;"
                />
            @endif

            {{-- Remove button --}}
            @if($removeMethod)
                <button
                    type="button"
                    wire:click="{{ $removeMethod }}"
                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600"
                    title="Remove image"
                >
                    ×
                </button>
            @endif
        </div>
    @endif

    {{-- File Input --}}
    <div class="flex items-center justify-center w-full">
        <label for="{{ $attributes->get('id', 'file-upload') }}" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                <svg class="w-8 h-8 mb-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                </svg>
                <p class="mb-2 text-sm text-gray-500">
                    <span class="font-semibold">Click to upload</span> or drag and drop
                </p>
                <p class="text-xs text-gray-500">PNG, JPG, GIF up to {{ $maxSize }}</p>
            </div>
            <input
                id="{{ $attributes->get('id', 'file-upload') }}"
                type="file"
                class="hidden"
                accept="{{ $accept }}"
                {{ $attributes->whereStartsWith('wire:') }}
            />
        </label>
    </div>

    {{-- Loading Indicator --}}
    <div wire:loading wire:target="{{ $attributes->whereStartsWith('wire:model')->first() }}" class="flex items-center space-x-2">
        <svg class="animate-spin h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-sm text-gray-600">Uploading...</span>
    </div>

    {{-- Error Display --}}
    @error($attributes->whereStartsWith('wire:model')->first())
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
