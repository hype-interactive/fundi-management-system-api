<?php

namespace App\Traits;

use App\Services\CloudinaryImageService;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

trait HasImageUpload
{
    protected CloudinaryImageService $imageService;

    /**
     * Initialize the image service
     */
    public function bootHasImageUpload()
    {
        $this->imageService = app(CloudinaryImageService::class);
    }

    /**
     * Handle single image upload
     *
     * @param UploadedFile|TemporaryUploadedFile $file
     * @param string $folder
     * @param string|null $oldPublicId
     * @param array $options
     * @return array|null
     */
    protected function handleImageUpload($file, string $folder = 'uploads', ?string $oldPublicId = null, array $options = []): ?array
    {
        if (!$file) {
            return null;
        }

        // Convert Livewire temporary file to UploadedFile if needed
        if ($file instanceof TemporaryUploadedFile) {
            $file = new UploadedFile(
                $file->getRealPath(),
                $file->getClientOriginalName(),
                $file->getMimeType(),
                null,
                true
            );
        }

        if ($oldPublicId) {
            return $this->imageService->update($file, $oldPublicId, $folder, $options);
        }

        return $this->imageService->upload($file, $folder, $options);
    }

    /**
     * Handle image deletion
     *
     * @param string $publicId
     * @return bool
     */
    protected function handleImageDeletion(string $publicId): bool
    {
        return $this->imageService->delete($publicId);
    }

    /**
     * Get optimized image URL
     *
     * @param string $publicId
     * @param array $transformations
     * @return string
     */
    protected function getOptimizedImageUrl(string $publicId, array $transformations = []): string
    {
        return $this->imageService->getOptimizedUrl($publicId, $transformations);
    }

    /**
     * Get thumbnail URL
     *
     * @param string $publicId
     * @param int $width
     * @param int $height
     * @return string
     */
    protected function getThumbnailUrl(string $publicId, int $width = 150, int $height = 150): string
    {
        return $this->imageService->getThumbnail($publicId, $width, $height);
    }

    /**
     * Validate image file
     *
     * @param mixed $file
     * @param array $rules
     * @return array
     */
    protected function validateImageFile($file, array $rules = []): array
    {
        $defaultRules = [
            'image',
            'max:2048', // 2MB max
            'mimes:jpeg,png,jpg,gif,webp'
        ];

        return array_merge($defaultRules, $rules);
    }

    /**
     * Get validation rules for image upload
     *
     * @param bool $required
     * @param array $additionalRules
     * @return array
     */
    protected function getImageValidationRules(bool $required = false, array $additionalRules = []): array
    {
        $rules = $required ? ['required'] : ['nullable'];

        return array_merge($rules, $this->validateImageFile(null, $additionalRules));
    }
}
