<?php

namespace App\Livewire;

use Exception;
use App\Models\Fundi;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\CloudinaryImageService;
use App\Traits\HasAlerts;

class FundiForm extends Component
{
    use WithFileUploads, HasAlerts;

    // Inject CloudinaryImageService
    protected $cloudinaryService;

    // Fundi form properties
    public $fundi_id = null;
    public $fname = '';
    public $mname = '';
    public $lname = '';
    public $gender = '';
    public $nida_number = '';
    public $primary_phone = '';
    public $transaction_phone = '';
    public $district = null;
    public $ward_id = null;
    public $ranking = '';
    public $profile_photo = null;
    public $status = 'active';
    public $legacy_match = false;
    public $legacy_id = '';

    // Cloudinary properties
    public $profile_photo_url = null;
    public $profile_photo_public_id = null;

    // Component compatibility
    public $ward = null;
    public $district_id = null;

    // UI state properties
    public $action_text = 'Create';
    public $editing = false;
    public $existing_photo = null;
    public $remove_photo = false;
    public $mode = 'create'; // 'create' or 'edit'
    public $saving = false; // Loading state

    // Districts and wards for dropdowns
    public $districts = [];
    public $wards = [];

    // Accept fundi model for editing
    public $fundi = null;

    protected $listeners = [
        'editFundi' => 'loadFundi',
        'createNewFundi' => 'resetForm',
        'resetValidation' => 'resetValidation',
        'refreshForm' => 'refreshFormData',
    ];

    public function boot()
    {
        $this->cloudinaryService = app(CloudinaryImageService::class);
    }

    public function mount($fundi = null)
    {
        if ($fundi) {
            $this->fundi = $fundi;
            $this->mode = 'edit';
            $this->loadFundiData($fundi);
        } else {
            $this->mode = 'create';
            $this->resetForm();
        }
        $this->loadDistricts();

        // Display any flashed alerts
        $this->displayFlashedAlerts();
    }

    // public function resetValidation()
    // {
    //     $this->resetErrorBag();
    // }

    /**
     * Load fundi data for editing
     */
    public function loadFundiData($fundi)
    {
        $this->fundi_id = $fundi->id;
        $this->fname = $fundi->fname;
        $this->mname = $fundi->mname;
        $this->lname = $fundi->lname;
        $this->gender = $fundi->gender;
        $this->nida_number = $fundi->nida_number;
        $this->primary_phone = $fundi->primary_phone;
        $this->transaction_phone = $fundi->transaction_phone;

        // Set district first to load wards correctly
        $this->district = $fundi->ward->district->id;
        $this->loadWards();
        $this->ward_id = $fundi->ward_id;
        $this->ward = $fundi->ward_id;

        $this->ranking = $fundi->ranking;
        $this->status = $fundi->status ?: 'active';
        $this->legacy_match = $fundi->legacy_match;
        $this->legacy_id = $fundi->legacy_id;

        // Handle existing Cloudinary photo
        $this->profile_photo_url = $fundi->profile_photo_url;
        $this->profile_photo_public_id = $fundi->profile_photo_public_id;

        if ($fundi->profile_photo_url) {
            $this->existing_photo = $fundi->profile_photo_url;
            $this->remove_photo = false;
        } else {
            $this->existing_photo = null;
        }

        $this->action_text = 'Update';
        $this->editing = true;
    }

    /**
     * Load all districts for the dropdown
     */
    public function loadDistricts()
    {
        $currentUser = currentUser();

        // If admin or root, get all districts, otherwise get user's districts
        if ($currentUser && ($currentUser->role->name === 'admin' || $currentUser->role->name === 'root')) {
            $this->districts = \App\Models\District::orderBy('name')->get();
        } else {
            $this->districts = $currentUser->districts;

            // Set default district to user's primary district
            if ($currentUser->primary_district && !$this->district) {
                $this->district = $currentUser->primary_district->id;
                $this->loadWards();
            }
        }
    }

    /**
     * Load wards for the selected district
     */
    public function loadWards()
    {
        if ($this->district) {
            $this->wards = \App\Models\Ward::where('district_id', $this->district)
                ->orderBy('name')
                ->get();
        } else {
            $this->wards = [];
        }

        // Reset ward selection if district changes
        if ($this->editing == false) {
            $this->ward_id = null;
        }
    }

    /**
     * Watch for district changes to update wards
     */
    public function updatedDistrict()
    {
        $this->loadWards();
    }

    // Add this watcher to sync ward to ward_id
    public function updatedWard($value)
    {
        $this->ward_id = $value;
    }

    /**
     * Reset the form to default values
     */
    public function resetForm()
    {
        $this->reset([
            'fundi_id',
            'fname',
            'mname',
            'lname',
            'gender',
            'nida_number',
            'primary_phone',
            'transaction_phone',
            'ward_id',
            'ranking',
            'profile_photo',
            'profile_photo_url',
            'profile_photo_public_id',
            'status',
            'legacy_match',
            'legacy_id',
            'editing',
            'existing_photo',
            'remove_photo',
            'saving'
        ]);

        // Set default values
        $this->action_text = 'Create';
        $this->status = 'active';
        $this->legacy_match = false;

        // Load user's primary district if available
        $currentUser = currentUser();
        if ($currentUser && $currentUser->primary_district) {
            $this->district = $currentUser->primary_district->id;
            $this->loadWards();
        }

        // $this->showInfoAlert('Form Reset', 'Form has been reset to default values.');
    }

    /**
     * Load an existing fundi for editing
     */
    public function loadFundi($fundiId)
    {
        try {
            $fundi = Fundi::findOrFail($fundiId);
            $this->resetForm();
            $this->loadFundiData($fundi);
            $this->showSuccessAlert('Fundi Loaded', 'Fundi data loaded successfully for editing.');
        } catch (Exception $e) {
            $this->showErrorAlert('Error', 'Failed to load fundi data. Please try again.');
            Log::error('Failed to load fundi: ' . $e->getMessage());
        }
    }

    /**
     * Refresh form data
     */
    public function refreshFormData()
    {
        $this->loadDistricts();
        if ($this->fundi_id) {
            $this->loadFundi($this->fundi_id);
        }
    }

    /**
     * Mark existing photo for removal
     */
    public function removePhoto()
    {
        $this->remove_photo = true;
        $this->existing_photo = null;
        $this->profile_photo_url = null;
        $this->profile_photo_public_id = null;

        $this->showWarningAlert('Photo Removed', 'Profile photo marked for removal. Save the form to confirm.');
    }

    /**
     * Test Cloudinary connection
     */
    public function testCloudinaryConnection()
    {
        $this->handleOperation(
            function () {
                return $this->cloudinaryService->testConnection();
            },
            'Connection Successful',
            'Cloudinary service is working properly.',
            'Connection Failed',
            'Unable to connect to image storage service.'
        );
    }

    /**
     * Create or update a fundi
     */
    public function createOrUpdateFundi()
    {
        // Set loading state
        $this->saving = true;

        // Validate form data
        $rules = [
            'fname' => 'required|string|max:255',
            'mname' => 'nullable|string|max:255',
            'lname' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'nida_number' => 'nullable|string|max:255',
            'primary_phone' => 'required|string|max:20|unique:fundis,primary_phone,' . $this->fundi_id,
            'transaction_phone' => 'nullable|string|max:20',
            'district' => 'required|exists:districts,id',
            'ward_id' => 'required|exists:wards,id',
            'ranking' => 'required|in:fundi_kuu,fundi_msaidizi',
            'status' => 'required|in:active,inactive,pending',
            'profile_photo' => 'nullable|image|max:2048',
            'legacy_id' => 'nullable|required_if:legacy_match,true|string|max:255',
        ];

        try {
            $this->validate($rules);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->saving = false;
            $this->showErrorAlert('Validation Error', 'Please check the form fields and try again.');
            throw $e;
        }

        $isCreating = empty($this->fundi_id);

        // Show loading alert
        $this->showLoadingAlert(
            $isCreating ? 'Creating Fundi' : 'Updating Fundi',
            'Please wait while we process your request...'
        );

        // Use the handleOperationWithFlash method for better error handling
        $result = $this->handleOperationWithFlash(
            function () use ($isCreating) {
                return $this->saveFundi($isCreating);
            },
            'Success',
            'Fundi ' . ($isCreating ? 'created' : 'updated') . ' successfully!',
            'Error',
            'Failed to ' . ($isCreating ? 'create' : 'update') . ' fundi. Please try again.'
        );

        $this->saving = false;

        if ($result) {
            // Handle different modes differently
            if ($this->mode === 'create') {
                // Reset form after saving and redirect
                $this->resetForm();
                $this->loadDistricts();
                return redirect('/fundis');
            } else {
                // For edit mode, reload the fundi data and stay on page
                $fundi = Fundi::find($this->fundi_id);
                if ($fundi) {
                    $this->loadFundiData($fundi);

                    // Dispatch event to refresh other components
                    $this->dispatch('fundiUpdated', 'Fundi updated successfully!');
                }
            }
        }
    }

    /**
     * Handle the actual saving logic
     */
    private function saveFundi($isCreating)
    {
        // Handle profile photo upload to Cloudinary
        $photo_url = $this->profile_photo_url;
        $photo_public_id = $this->profile_photo_public_id;

        // Handle photo upload
        if ($this->profile_photo) {
            $photoResult = $this->handlePhotoUpload();
            if (!$photoResult) {
                return false; // Photo upload failed
            }
            $photo_url = $photoResult['url'];
            $photo_public_id = $photoResult['public_id'];
        } elseif ($this->remove_photo && $this->editing) {
            // Remove existing photo
            $this->handlePhotoRemoval();
            $photo_url = null;
            $photo_public_id = null;
        }

        // Prepare data for create/update
        $fundiData = [
            'fname' => $this->fname,
            'mname' => $this->mname,
            'lname' => $this->lname,
            'gender' => $this->gender,
            'nida_number' => $this->nida_number,
            'primary_phone' => $this->primary_phone,
            'transaction_phone' => $this->transaction_phone,
            'ward_id' => $this->ward_id,
            'ranking' => $this->ranking,
            'profile_photo_url' => $photo_url,
            'profile_photo_public_id' => $photo_public_id,
            'status' => $isCreating ? 'pending' : $this->status,
            'legacy_match' => $this->legacy_match,
            'legacy_id' => $this->legacy_match ? $this->legacy_id : null,
        ];

        // Only add unique_id and created_by for new records
        if ($isCreating) {
            $fundiData['unique_id'] = 'F' . date('Ymd') . rand(1000, 9999);
            $fundiData['created_by'] = Auth::id();
        }

        // Create or update the fundi
        $fundi = Fundi::updateOrCreate(
            ['id' => $this->fundi_id],
            $fundiData
        );

        return $fundi ? true : false;
    }

    /**
     * Handle photo upload with error handling
     */
    private function handlePhotoUpload()
    {
        try {
            // Test Cloudinary connection first
            if (!$this->cloudinaryService->testConnection()) {
                throw new Exception('Unable to connect to image storage service.');
            }

            if ($this->editing && $this->profile_photo_public_id) {
                // Update existing image (delete old, upload new)
                $uploadResult = $this->cloudinaryService->update(
                    $this->profile_photo,
                    $this->profile_photo_public_id,
                    'FundiApp/Fundis'
                );
            } else {
                // Upload new image
                $uploadResult = $this->cloudinaryService->upload(
                    $this->profile_photo,
                    'FundiApp/Fundis'
                );
            }

            if ($uploadResult && isset($uploadResult['url']) && isset($uploadResult['public_id'])) {
                Log::info('Photo uploaded successfully', [
                    'url' => $uploadResult['url'],
                    'public_id' => $uploadResult['public_id']
                ]);

                return [
                    'url' => $uploadResult['url'],
                    'public_id' => $uploadResult['public_id']
                ];
            } else {
                Log::error('Photo upload failed - invalid result', [
                    'result' => $uploadResult
                ]);
                throw new Exception('Invalid upload result from Cloudinary service.');
            }
        } catch (Exception $e) {
            Log::error('Photo upload exception: ' . $e->getMessage());
            throw new Exception('Photo upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle photo removal with error handling
     */
    private function handlePhotoRemoval()
    {
        try {
            if ($this->profile_photo_public_id) {
                $deleteResult = $this->cloudinaryService->delete($this->profile_photo_public_id);
                if (!$deleteResult) {
                    Log::warning('Failed to delete old photo', [
                        'public_id' => $this->profile_photo_public_id
                    ]);
                }
            }
        } catch (Exception $e) {
            Log::error('Photo deletion exception: ' . $e->getMessage());
            // Continue with saving even if deletion fails
        }
    }

    /**
     * Cancel form editing
     */
    public function cancelEdit()
    {
        if ($this->editing && $this->fundi) {
            // Reload original data
            $this->loadFundiData($this->fundi);
            $this->showInfoAlert('Changes Cancelled', 'Form has been reset to original values.');
        } else {
            // Reset form for new creation
            $this->resetForm();
        }
    }

    /**
     * Duplicate current fundi
     */
    public function duplicateFundi()
    {
        if (!$this->editing) {
            $this->showWarningAlert('Cannot Duplicate', 'You can only duplicate an existing fundi.');
            return;
        }

        $this->showConfirmation(
            'Duplicate Fundi',
            'This will create a new fundi with the same details. You can modify the details before saving.',
            'confirmDuplicateFundi'
        );
    }

    /**
     * Confirm duplication
     */
    public function confirmDuplicateFundi()
    {
        // Clear ID and reset to create mode
        $this->fundi_id = null;
        $this->editing = false;
        $this->mode = 'create';
        $this->action_text = 'Create';

        // Clear unique fields
        $this->primary_phone = '';
        $this->nida_number = '';
        $this->profile_photo_url = null;
        $this->profile_photo_public_id = null;
        $this->existing_photo = null;

        $this->showSuccessAlert('Fundi Duplicated', 'Fundi data copied for new creation. Please update the phone number and other unique fields.');
    }

    /**
     * Delete current fundi (if editing)
     */
    public function deleteFundi()
    {
        if (!$this->editing || !$this->fundi_id) {
            $this->showWarningAlert('Cannot Delete', 'No fundi selected for deletion.');
            return;
        }

        $this->showConfirmation(
            'Delete Fundi',
            'Are you sure you want to delete this fundi? This action cannot be undone.',
            'confirmDeleteFundi'
        );
    }

    /**
     * Confirm deletion
     */
    public function confirmDeleteFundi()
    {
        $result = $this->handleOperationWithFlash(
            function () {
                $fundi = Fundi::find($this->fundi_id);
                if ($fundi) {
                    // Delete photo from Cloudinary if exists
                    if ($fundi->profile_photo_public_id) {
                        $this->cloudinaryService->delete($fundi->profile_photo_public_id);
                    }
                    return $fundi->delete();
                }
                return false;
            },
            'Success',
            'Fundi deleted successfully!',
            'Error',
            'Failed to delete fundi.'
        );

        if ($result) {
            // Redirect to fundis list
            return redirect('/fundis');
        }
    }

    /**
     * Validate specific field
     */
    public function validateField($field)
    {
        $rules = [
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'primary_phone' => 'required|string|max:20|unique:fundis,primary_phone,' . $this->fundi_id,
            'district' => 'required|exists:districts,id',
            'ward_id' => 'required|exists:wards,id',
            'ranking' => 'required|in:fundi_kuu,fundi_msaidizi',
        ];

        if (isset($rules[$field])) {
            $this->validateOnly($field, [$field => $rules[$field]]);
        }
    }

    /**
     * Auto-save draft (if needed)
     */
    public function saveDraft()
    {
        // This could be implemented to save form data to session or temporary storage
        $this->showInfoAlert('Draft Saved', 'Your progress has been saved as a draft.');
    }

    public function render()
    {
        return view('livewire.fundi-form');
    }
}
