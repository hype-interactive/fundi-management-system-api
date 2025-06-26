<?php

namespace App\Services;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Facades\Log;
use Exception;

class CloudinaryImageService
{
    /**
     * Upload an image to Cloudinary
     *
     * @param UploadedFile|TemporaryUploadedFile $file
     * @param string $folder
     * @param array $options
     * @return array|null
     */
    public function upload($file, string $folder = 'uploads', array $options = []): ?array
    {
        try {
            // Validate file
            if (!$file || !$file->isValid()) {
                Log::error('Invalid file provided for upload');
                return null;
            }

            // Default options
            $defaultOptions = [
                'folder' => $folder,
                'quality' => 'auto',
                'fetch_format' => 'auto'
            ];

            // Merge with provided options
            $uploadOptions = array_merge($defaultOptions, $options);

            Log::info('Attempting Cloudinary upload', [
                'file_type' => get_class($file),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'folder' => $folder
            ]);

            // Handle Livewire TemporaryUploadedFile
            if ($file instanceof TemporaryUploadedFile) {
                // Get the real path of the temporary file
                $filePath = $file->getRealPath();

                if (!$filePath || !file_exists($filePath)) {
                    throw new Exception('Temporary file path not found or file does not exist');
                }

                Log::info('Uploading TemporaryUploadedFile', ['path' => $filePath]);

                // Upload using Cloudinary's uploadApi
                $result = Cloudinary::uploadApi()->upload($filePath, $uploadOptions);

                Log::info('Cloudinary upload result', ['result' => $result]);

                if (!$result || !isset($result['secure_url']) || !isset($result['public_id'])) {
                    throw new Exception('Invalid response from Cloudinary: ' . json_encode($result));
                }

                return [
                    'url' => $result['secure_url'],
                    'public_id' => $result['public_id'],
                    'original_filename' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType()
                ];
            }
            // Handle regular UploadedFile
            elseif ($file instanceof UploadedFile) {
                Log::info('Uploading regular UploadedFile');

                // Use the storeOnCloudinary method for regular uploaded files
                $cloudinaryImage = $file->storeOnCloudinary($folder, $uploadOptions);

                if (!$cloudinaryImage) {
                    throw new Exception('Failed to upload file to Cloudinary');
                }

                return [
                    'url' => $cloudinaryImage->getSecurePath(),
                    'public_id' => $cloudinaryImage->getPublicId(),
                    'original_filename' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType()
                ];
            }

            throw new Exception('Unsupported file type: ' . get_class($file));

        } catch (Exception $e) {
            Log::error('Cloudinary upload failed: ' . $e->getMessage(), [
                'file_type' => isset($file) ? get_class($file) : 'null',
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Update an image (delete old and upload new)
     *
     * @param UploadedFile|TemporaryUploadedFile $newFile
     * @param string|null $oldPublicId
     * @param string $folder
     * @param array $options
     * @return array|null
     */
    public function update($newFile, ?string $oldPublicId = null, string $folder = 'uploads', array $options = []): ?array
    {
        try {
            // Delete old image if exists
            if ($oldPublicId) {
                $this->delete($oldPublicId);
            }

            // Upload new image
            return $this->upload($newFile, $folder, $options);

        } catch (Exception $e) {
            Log::error('Cloudinary update failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete an image from Cloudinary
     *
     * @param string $publicId
     * @return bool
     */
    public function delete(string $publicId): bool
    {
        try {
            $result = Cloudinary::destroy($publicId);
            return $result['result'] === 'ok';

        } catch (Exception $e) {
            Log::error('Cloudinary delete failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get optimized image URL with transformations
     *
     * @param string $publicId
     * @param array $transformations
     * @return string
     */
    public function getOptimizedUrl(string $publicId, array $transformations = []): string
    {
        try {
            $defaultTransformations = [
                'quality' => 'auto',
                'fetch_format' => 'auto'
            ];

            $transformations = array_merge($defaultTransformations, $transformations);

            // Use Cloudinary's URL generation
            return Cloudinary::url($publicId, $transformations);

        } catch (Exception $e) {
            Log::error('Failed to generate optimized URL: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Upload multiple images
     *
     * @param array $files
     * @param string $folder
     * @param array $options
     * @return array
     */
    public function uploadMultiple(array $files, string $folder = 'uploads', array $options = []): array
    {
        $results = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile || $file instanceof TemporaryUploadedFile) {
                $result = $this->upload($file, $folder, $options);
                if ($result) {
                    $results[] = $result;
                }
            }
        }

        return $results;
    }

    /**
     * Delete multiple images
     *
     * @param array $publicIds
     * @return array
     */
    public function deleteMultiple(array $publicIds): array
    {
        $results = [];

        foreach ($publicIds as $publicId) {
            $results[$publicId] = $this->delete($publicId);
        }

        return $results;
    }

    /**
     * Generate thumbnail URL
     *
     * @param string $publicId
     * @param int $width
     * @param int $height
     * @param string $crop
     * @return string
     */
    public function getThumbnail(string $publicId, int $width = 150, int $height = 150, string $crop = 'fill'): string
    {
        return $this->getOptimizedUrl($publicId, [
            'width' => $width,
            'height' => $height,
            'crop' => $crop
        ]);
    }

    /**
     * Generate responsive image URLs
     *
     * @param string $publicId
     * @param array $breakpoints
     * @return array
     */
    public function getResponsiveUrls(string $publicId, array $breakpoints = [320, 640, 768, 1024, 1280]): array
    {
        $urls = [];

        foreach ($breakpoints as $width) {
            $urls[$width] = $this->getOptimizedUrl($publicId, [
                'width' => $width,
                'crop' => 'scale'
            ]);
        }

        return $urls;
    }
}
