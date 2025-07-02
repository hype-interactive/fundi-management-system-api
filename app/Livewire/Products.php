<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Component;
use App\Traits\HasAlerts;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;
use App\Services\CloudinaryImageService;

class Products extends Component
{
    use HasAlerts, WithFileUploads;

    public $products;
    public $product;
    public $product_id;

    public $name;
    public $description;
    public $visibility = 'visible';
    public $availability = 'available';
    public $photo;
    public $current_photo_url;
    public $current_photo_public_id;

    public $action_text;
    public $is_editing = false;

    protected CloudinaryImageService $cloudinaryService;

    public function boot(CloudinaryImageService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    public function mount()
    {
        $this->action_text = "Create";

        // Display any flashed alerts
        $this->displayFlashedAlerts();
    }

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:1000',
        'visibility' => 'required|in:visible,hidden',
        'availability' => 'required|in:available,unavailable',
        'photo' => 'nullable|image|max:5120', // 5MB max
    ];

    protected $validationAttributes = [
        'name' => 'product name',
        'description' => 'description',
        'visibility' => 'visibility',
        'availability' => 'availability',
        'photo' => 'product photo',
    ];

    /**
     * Refresh form - reset to create mode
     */
    public function refresh()
    {
        $this->reset([
            'name',
            'description',
            'visibility',
            'availability',
            'photo',
            'current_photo_url',
            'current_photo_public_id'
        ]);

        $this->action_text = "Create";
        $this->product_id = null;
        $this->is_editing = false;
        $this->visibility = 'visible';
        $this->availability = 'available';
    }

    /**
     * Set product details for editing
     */
    public function setProduct($product)
    {
        $this->action_text = "Update";
        $this->is_editing = true;

        $this->product = Product::find($product['id']);

        if ($this->product) {
            $this->product_id = $this->product->id;
            $this->name = $this->product->name;
            $this->description = $this->product->description;
            $this->visibility = $this->product->visibility;
            $this->availability = $this->product->availability;
            $this->current_photo_url = $this->product->photo_url;
            $this->current_photo_public_id = $this->product->photo_public_id;
        }
    }

    /**
     * Create or update a product
     */
    public function createOrUpdateProduct()
    {
        $this->validate();

        // Use the handleOperationWithFlash method for cleaner code
        $result = $this->handleOperationWithFlash(
            function () {
                // Handle photo upload if new photo is provided
                $photoData = $this->handlePhotoUpload();

                if ($photoData === false) {
                    // Photo upload failed
                    return false;
                }

                // Prepare product data
                $productData = [
                    'name' => $this->name,
                    'description' => $this->description,
                    'visibility' => $this->visibility,
                    'availability' => $this->availability,
                ];

                // Add photo data if available
                if ($photoData) {
                    $productData['photo_url'] = $photoData['url'];
                    $productData['photo_public_id'] = $photoData['public_id'];
                }

                // Create or update product
                $product = Product::updateOrCreate(
                    ['id' => $this->product_id],
                    $productData
                );

                return $product ? true : false;
            },
            'Success',
            'Product ' . strtolower($this->action_text) . 'd successfully.',
            'Error',
            'Failed to ' . strtolower($this->action_text) . ' product.'
        );

        if ($result) {
            // Reset form fields
            $this->refresh();

            // Redirect to /products where the alert will be shown in mount()
            return redirect('/products');
        }
    }

    /**
     * Handle photo upload to Cloudinary
     */
    private function handlePhotoUpload()
    {
        // If no new photo is uploaded, return null (keep existing photo)
        if (!$this->photo) {
            return null;
        }

        try {
            // Upload new photo
            $uploadResult = $this->cloudinaryService->upload(
                $this->photo,
                'products', // folder
                [
                    'width' => 1200,
                    'height' => 800,
                    'crop' => 'limit',
                    'quality' => 'auto:good'
                ]
            );

            if (!$uploadResult) {
                Log::error('Failed to upload product photo to Cloudinary');
                return false;
            }

            // If updating and old photo exists, delete it
            if ($this->is_editing && $this->current_photo_public_id) {
                $deleteResult = $this->cloudinaryService->delete($this->current_photo_public_id);
                if (!$deleteResult) {
                    Log::warning('Failed to delete old product photo', [
                        'public_id' => $this->current_photo_public_id
                    ]);
                }
            }

            return $uploadResult;

        } catch (\Exception $e) {
            Log::error('Error handling product photo upload: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a product
     */
    public function deleteProduct($productId)
    {
        try {
            $product = Product::find($productId);

            if (!$product) {
                session()->flash('error', "Product not found.");
                return;
            }

            // Delete photo from Cloudinary if exists
            if ($product->photo_public_id) {
                $deleteResult = $this->cloudinaryService->delete($product->photo_public_id);
                if (!$deleteResult) {
                    Log::warning('Failed to delete product photo from Cloudinary', [
                        'public_id' => $product->photo_public_id
                    ]);
                }
            }

            // Delete the product
            $product->delete();
            session()->flash('message', "Product deleted successfully.");

        } catch (\Exception $e) {
            Log::error('Error deleting product: ' . $e->getMessage());
            session()->flash('error', "Failed to delete product.");
        }
    }

    /**
     * Toggle product visibility
     */
    public function toggleVisibility($productId)
    {
        try {
            $product = Product::find($productId);

            if ($product) {
                $product->visibility = $product->visibility === 'visible' ? 'hidden' : 'visible';
                $product->save();

                $status = $product->visibility === 'visible' ? 'shown' : 'hidden';
                session()->flash('message', "Product {$status} successfully.");
            } else {
                session()->flash('error', "Product not found.");
            }
        } catch (\Exception $e) {
            Log::error('Error toggling product visibility: ' . $e->getMessage());
            session()->flash('error', "Failed to update product visibility.");
        }
    }

    /**
     * Toggle product availability
     */
    public function toggleAvailability($productId)
    {
        try {
            $product = Product::find($productId);

            if ($product) {
                $product->availability = $product->availability === 'available' ? 'unavailable' : 'available';
                $product->save();

                $status = $product->availability === 'available' ? 'available' : 'unavailable';
                session()->flash('message', "Product marked as {$status} successfully.");
            } else {
                session()->flash('error', "Product not found.");
            }
        } catch (\Exception $e) {
            Log::error('Error toggling product availability: ' . $e->getMessage());
            session()->flash('error', "Failed to update product availability.");
        }
    }

    /**
     * Get thumbnail URL for product photo
     */
    public function getThumbnail($publicId, $width = 200, $height = 200)
    {
        if (!$publicId) {
            return null;
        }

        return $this->cloudinaryService->getThumbnail($publicId, $width, $height);
    }

    public function render()
    {
        $this->products = Product::orderBy('created_at', 'desc')->get();

        return view('livewire.products');
    }
}
