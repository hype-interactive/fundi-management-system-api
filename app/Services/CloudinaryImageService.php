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
                'fetch_format' => 'auto',
                'resource_type' => 'image'
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
                return $this->uploadTemporaryFile($file, $uploadOptions);
            }
            // Handle regular UploadedFile
            elseif ($file instanceof UploadedFile) {
                return $this->uploadRegularFile($file, $folder, $uploadOptions);
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
     * Convert Cloudinary ApiResponse to array
     *
     * @param mixed $response
     * @return array|null
     */
    private function convertResponseToArray($response): ?array
    {
        if (is_array($response)) {
            return $response;
        }

        if (is_object($response)) {
            // If it's an ApiResponse object, try to convert to array
            if (method_exists($response, 'toArray')) {
                return $response->toArray();
            }

            // If it has getArrayCopy method
            if (method_exists($response, 'getArrayCopy')) {
                return $response->getArrayCopy();
            }

            // Try to access as array (ArrayAccess interface)
            if ($response instanceof \ArrayAccess) {
                $array = [];
                $reflection = new \ReflectionClass($response);
                if ($reflection->hasProperty('data') || isset($response['secure_url'])) {
                    // Common Cloudinary response fields
                    $fields = ['secure_url', 'public_id', 'width', 'height', 'format', 'resource_type', 'created_at'];
                    foreach ($fields as $field) {
                        if (isset($response[$field])) {
                            $array[$field] = $response[$field];
                        }
                    }
                    return $array;
                }
            }

            // Last resort: convert object to array
            return json_decode(json_encode($response), true);
        }

        return null;
    }

    /**
     * Upload a Livewire TemporaryUploadedFile
     *
     * @param TemporaryUploadedFile $file
     * @param array $uploadOptions
     * @return array|null
     */
    private function uploadTemporaryFile(TemporaryUploadedFile $file, array $uploadOptions): ?array
    {
        // Get the real path of the temporary file
        $filePath = $file->getRealPath();

        if (!$filePath || !file_exists($filePath)) {
            throw new Exception('Temporary file path not found or file does not exist');
        }

        Log::info('Uploading TemporaryUploadedFile', [
            'path' => $filePath,
            'file_exists' => file_exists($filePath),
            'file_size' => filesize($filePath)
        ]);

        // Try multiple approaches for uploading
        $result = null;

        // Method 1: Direct file path upload
        try {
            $apiResponse = Cloudinary::uploadApi()->upload($filePath, $uploadOptions);
            $result = $this->convertResponseToArray($apiResponse);

            if ($result) {
                Log::info('Method 1 (direct path) succeeded', [
                    'has_secure_url' => isset($result['secure_url']),
                    'has_public_id' => isset($result['public_id'])
                ]);
            }
        } catch (Exception $e) {
            Log::warning('Method 1 (direct path) failed: ' . $e->getMessage());
        }

        // Method 2: Using file contents if Method 1 failed
        if (!$result || !isset($result['secure_url'])) {
            try {
                $fileContents = file_get_contents($filePath);
                if ($fileContents === false) {
                    throw new Exception('Could not read file contents');
                }

                // Create a data URI
                $mimeType = $file->getMimeType() ?: 'image/jpeg';
                $dataUri = 'data:' . $mimeType . ';base64,' . base64_encode($fileContents);

                $apiResponse = Cloudinary::uploadApi()->upload($dataUri, $uploadOptions);
                $result = $this->convertResponseToArray($apiResponse);

                if ($result) {
                    Log::info('Method 2 (data URI) succeeded', [
                        'has_secure_url' => isset($result['secure_url']),
                        'has_public_id' => isset($result['public_id'])
                    ]);
                }
            } catch (Exception $e) {
                Log::warning('Method 2 (data URI) failed: ' . $e->getMessage());
            }
        }

        // Method 3: Copy to temporary location and upload
        if (!$result || !isset($result['secure_url'])) {
            try {
                $tempPath = tempnam(sys_get_temp_dir(), 'cloudinary_upload_');
                if (copy($filePath, $tempPath)) {
                    $apiResponse = Cloudinary::uploadApi()->upload($tempPath, $uploadOptions);
                    $result = $this->convertResponseToArray($apiResponse);
                    unlink($tempPath); // Clean up

                    if ($result) {
                        Log::info('Method 3 (temp copy) succeeded', [
                            'has_secure_url' => isset($result['secure_url']),
                            'has_public_id' => isset($result['public_id'])
                        ]);
                    }
                }
            } catch (Exception $e) {
                Log::warning('Method 3 (temp copy) failed: ' . $e->getMessage());
            }
        }

        // Validate the result
        if (!$result) {
            throw new Exception('All upload methods failed - no result returned');
        }

        if (!is_array($result)) {
            throw new Exception('Invalid response type from Cloudinary: ' . gettype($result));
        }

        if (!isset($result['secure_url']) || !isset($result['public_id'])) {
            throw new Exception('Invalid response from Cloudinary - missing required fields. Response: ' . json_encode($result));
        }

        return [
            'url' => $result['secure_url'],
            'public_id' => $result['public_id'],
            'original_filename' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'width' => $result['width'] ?? null,
            'height' => $result['height'] ?? null,
            'format' => $result['format'] ?? null
        ];
    }

    /**
     * Upload a regular UploadedFile
     *
     * @param UploadedFile $file
     * @param string $folder
     * @param array $uploadOptions
     * @return array|null
     */
    private function uploadRegularFile(UploadedFile $file, string $folder, array $uploadOptions): ?array
    {
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
            'mime_type' => $file->getMimeType(),
            'width' => $cloudinaryImage->getWidth(),
            'height' => $cloudinaryImage->getHeight(),
            'format' => $cloudinaryImage->getFormat()
        ];
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
            // Upload new image first
            $uploadResult = $this->upload($newFile, $folder, $options);

            if (!$uploadResult) {
                throw new Exception('Failed to upload new image');
            }

            // Delete old image if exists and new upload was successful
            if ($oldPublicId) {
                $deleteResult = $this->delete($oldPublicId);
                if (!$deleteResult) {
                    Log::warning('Failed to delete old image', ['public_id' => $oldPublicId]);
                }
            }

            return $uploadResult;

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
        Log::info('Attempting to delete image', ['public_id' => $publicId]);

        // Use the adminApi()->deleteAssets() method
        $result = Cloudinary::adminApi()->deleteAssets([$publicId]);

        Log::info('Delete result', ['result' => $result]);

        return !empty($result['deleted'][$publicId]) && $result['deleted'][$publicId] === 'deleted';

    } catch (Exception $e) {
        Log::error('Cloudinary delete failed: ' . $e->getMessage(), ['public_id' => $publicId]);
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

            // WRONG - This causes the error:
            // return Cloudinary::url($publicId, $transformations);

            // CORRECT - Use cloudinary_url instead:
            return Cloudinary::image($publicId, $transformations);

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

    /**
     * Test Cloudinary connection
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            // Try to get account information
            $result = Cloudinary::adminApi()->ping();

            // Convert result to array if it's an ApiResponse object
            if (!is_array($result)) {
                $result = $this->convertResponseToArray($result);
            }

            return isset($result['status']) && $result['status'] === 'ok';
        } catch (Exception $e) {
            Log::error('Cloudinary connection test failed: ' . $e->getMessage());
            return false;
        }
    }
}
