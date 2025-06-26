<?php

namespace App\Livewire;

use App\Models\Fundi;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Services\CloudinaryImageService;

class FundiForm extends Component
{
    use WithFileUploads;

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

    // Districts and wards for dropdowns
    public $districts = [];
    public $wards = [];

    // Accept fundi model for editing
    public $fundi = null;

    protected $listeners = [
        'editFundi' => 'loadFundi',
        'createNewFundi' => 'resetForm',
        'resetValidation' => 'resetValidation',
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
            'remove_photo'
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
    }

    /**
     * Load an existing fundi for editing
     */
    public function loadFundi($fundiId)
    {
        $fundi = Fundi::findOrFail($fundiId);

        $this->resetForm();

        $this->loadFundiData($fundi);
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
    }

    /**
     * Create or update a fundi
     */
    public function createOrUpdateFundi()
    {
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
            'profile_photo' => $this->fundi_id ? 'nullable|image|max:2048' : 'nullable|image|max:2048',
            'legacy_id' => 'nullable|required_if:legacy_match,true|string|max:255',
        ];

        $this->validate($rules);

        $isCreating = empty($this->fundi_id);

        // Handle profile photo upload to Cloudinary
        $photo_url = $this->profile_photo_url;
        $photo_public_id = $this->profile_photo_public_id;

        if ($this->profile_photo) {
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

            if ($uploadResult) {
                $photo_url = $uploadResult['url'];
                $photo_public_id = $uploadResult['public_id'];
            } else {
                session()->flash('error', 'Failed to upload profile photo');
                return;
            }
        } elseif ($this->remove_photo && $this->editing) {
            // Remove existing photo
            if ($this->profile_photo_public_id) {
                $this->cloudinaryService->delete($this->profile_photo_public_id);
            }
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

        if ($fundi) {
            // Handle different modes differently
            if ($this->mode === 'create') {
                // Reset form after saving
                $this->resetForm();
                $this->loadDistricts();
                // Redirect to fundi list page
                return redirect('/fundis');
            } else {
                // For edit mode, just show success message and stay on page
                // Optionally reload the fundi data
                $this->loadFundiData($fundi);
            }
        } else {
            session()->flash('saved', [
                'title' => 'Error',
                'text' => 'Failed to ' . ($this->editing ? 'update' : 'create') . ' fundi.',
            ]);
        }

        // Reset form after saving
        $this->resetForm();
        $this->loadDistricts();

        // Redirect to fundi list page
        return redirect('/fundis');
    }

    public function render()
    {
        return view('livewire.fundi-form');
    }
}
