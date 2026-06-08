<?php

namespace App\Livewire;

use App\Models\Product;
use App\Services\CloudinaryImageService;
use App\Traits\HasAlerts;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Products extends Component
{
    use HasAlerts, WithFileUploads, WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Search and filters
    #[Url]
    public $search_key = '';

    #[Url]
    public $visibility_filter = '';

    #[Url]
    public $availability_filter = '';

    public $perPage = 6; // Good number for product grid

    // Product form properties
    public $product;

    public $product_id;

    public $name;

    public $description;

    public $visibility = 'public';

    public $availability = 'available';

    public $photo;

    public $current_photo_url;

    public $current_photo_public_id;

    public $action_text;

    public $is_editing = false;

    // Mobile responsiveness
    public $showMobileFilters = false;

    public $compactView = false;

    protected CloudinaryImageService $cloudinaryService;

    public function boot(CloudinaryImageService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    public function mount()
    {
        $this->action_text = 'Create';

        // Display any flashed alerts
        $this->displayFlashedAlerts();
    }

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:1000',
        'visibility' => 'required|in:public,private',
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

    // Pagination reset methods
    public function updatedSearchKey()
    {
        $this->resetPage();
    }

    public function updatedVisibilityFilter()
    {
        $this->resetPage();
    }

    public function updatedAvailabilityFilter()
    {
        $this->resetPage();
    }

    /**
     * Get products query with filters and pagination
     */
    public function getProductsQuery()
    {
        $query = Product::query();

        // Apply search filter
        if ($this->search_key) {
            $query->where(function ($subQuery) {
                $subQuery->where('name', 'like', '%'.$this->search_key.'%')
                    ->orWhere('description', 'like', '%'.$this->search_key.'%');
            });
        }

        // Apply visibility filter
        if ($this->visibility_filter) {
            $query->where('visibility', $this->visibility_filter);
        }

        // Apply availability filter
        if ($this->availability_filter) {
            $query->where('availability', $this->availability_filter);
        }

        return $query->orderBy('created_at', 'asc');
    }

    /**
     * Fetch paginated products
     */
    public function getProducts()
    {
        return $this->getProductsQuery()->paginate($this->perPage);
    }

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
            'current_photo_public_id',
        ]);

        $this->action_text = 'Create';
        $this->product_id = null;
        $this->is_editing = false;
        $this->visibility = 'public';
        $this->availability = 'available';
    }

    /**
     * Clear all filters
     */
    public function clearFilters()
    {
        $this->reset([
            'search_key',
            'visibility_filter',
            'availability_filter',
        ]);
        $this->showMobileFilters = false;
    }

    /**
     * Toggle mobile filters
     */
    public function toggleMobileFilters()
    {
        $this->showMobileFilters = ! $this->showMobileFilters;
    }

    /**
     * Toggle compact view
     */
    public function toggleCompactView()
    {
        $this->compactView = ! $this->compactView;
    }

    /**
     * Set product details for editing
     */
    public function setProduct($product)
    {
        $this->action_text = 'Update';
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
            'Product '.strtolower($this->action_text).'d successfully.',
            'Error',
            'Failed to '.strtolower($this->action_text).' product.'
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
        if (! $this->photo) {
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
                    'quality' => 'auto:good',
                ]
            );

            if (! $uploadResult) {
                Log::error('Failed to upload product photo to Cloudinary');

                return false;
            }

            // If updating and old photo exists, delete it
            if ($this->is_editing && $this->current_photo_public_id) {
                $deleteResult = $this->cloudinaryService->delete($this->current_photo_public_id);
                if (! $deleteResult) {
                    Log::warning('Failed to delete old product photo', [
                        'public_id' => $this->current_photo_public_id,
                    ]);
                }
            }

            return $uploadResult;
        } catch (\Exception $e) {
            Log::error('Error handling product photo upload: '.$e->getMessage());

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

            if (! $product) {
                session()->flash('error', 'Product not found.');

                return;
            }

            // Delete photo from Cloudinary if exists
            if ($product->photo_public_id) {
                $deleteResult = $this->cloudinaryService->delete($product->photo_public_id);
                if (! $deleteResult) {
                    Log::warning('Failed to delete product photo from Cloudinary', [
                        'public_id' => $product->photo_public_id,
                    ]);
                }
            }

            // Delete the product
            $product->delete();
            session()->flash('message', 'Product deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting product: '.$e->getMessage());
            session()->flash('error', 'Failed to delete product.');
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
                $product->visibility = $product->visibility === 'public' ? 'private' : 'public';
                $product->save();

                $status = $product->visibility === 'public' ? 'shown' : 'hidden';
                session()->flash('message', "Product {$status} successfully.");
            } else {
                session()->flash('error', 'Product not found.');
            }
        } catch (\Exception $e) {
            Log::error('Error toggling product visibility: '.$e->getMessage());
            session()->flash('error', 'Failed to update product visibility.');
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
                session()->flash('error', 'Product not found.');
            }
        } catch (\Exception $e) {
            Log::error('Error toggling product availability: '.$e->getMessage());
            session()->flash('error', 'Failed to update product availability.');
        }
    }

    /**
     * Get thumbnail URL for product photo
     */
    public function getThumbnail($publicId, $width = 200, $height = 200)
    {
        if (! $publicId) {
            return null;
        }

        return $this->cloudinaryService->getThumbnail($publicId, $width, $height);
    }

    public function render()
    {
        return view('livewire.products', [
            'products' => $this->getProducts(),
        ]);
    }
}
