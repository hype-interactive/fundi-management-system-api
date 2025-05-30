<?php

namespace App\Livewire;

use App\Models\Role;
use App\Models\User;
use App\Models\Region;
use Livewire\Component;
use App\Models\District;
use Livewire\WithFileUploads;
use App\Traits\HasImageUpload;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class OperatorForm extends Component
{
    use WithFileUploads, HasImageUpload;

    // Operator form properties
    public $operator_id = null;
    public $fname = '';
    public $mname = '';
    public $lname = '';
    public $email = '';
    public $phone = '';
    public $role_id = null;
    public $status = 'active';
    public $profile_photo = null;
    public $password = '';
    public $password_confirmation = '';

    // Image handling properties
    public $existing_photo_url = null;
    public $existing_photo_public_id = null;
    public $remove_photo = false;

    // Region and District assignment - CHANGED: Single district instead of multiple
    public $selected_region = null;
    public $selected_district = null; // CHANGED: Single district ID
    public $available_districts = [];

    // UI state properties
    public $action_text = 'Create';
    public $editing = false;
    public $mode = 'create'; // 'create' or 'edit'
    public $show_password = false;

    // Available roles and districts
    public $roles = [];
    public $regions = [];
    public $districts;

    // Accept operator model for editing
    public $operator = null;

    protected $listeners = [
        'editOperator' => 'loadOperator',
        'createNewOperator' => 'resetForm',
        'resetValidation' => 'resetValidation',
    ];

    public function mount($operator = null)
    {
        if ($operator) {
            $this->operator = $operator;
            $this->mode = 'edit';
            $this->loadOperatorData($operator);
        } else {
            $this->mode = 'create';
            $this->resetForm();
        }
        $this->loadRoles();
        $this->loadRegions();
    }

    /**
     * Load operator data for editing
     */
    public function loadOperatorData($operator)
    {
        $this->operator_id = $operator->id;
        $this->fname = $operator->fname;
        $this->mname = $operator->mname;
        $this->lname = $operator->lname;
        $this->email = $operator->email;
        $this->phone = $operator->phone;
        $this->role_id = $operator->role_id;
        $this->status = $operator->status ?: 'active';

        // Load assigned districts and determine region - BACKWARD COMPATIBLE
        $assignedDistricts = $operator->districts;
        if ($assignedDistricts->count() > 0) {
            $firstDistrict = $assignedDistricts->first();

            // For backward compatibility: if operator has multiple districts, use the first one
            $this->selected_district = $firstDistrict->id;
            $this->selected_region = $firstDistrict->region_id;
            $this->loadDistrictsByRegion();

            // Optional: Log or notify if operator had multiple districts (for migration tracking)
            if ($assignedDistricts->count() > 1) {
                // You can add logging here if needed
                Log::info("Operator {$operator->id} had multiple districts, using first one: {$firstDistrict->name}");
            }
        }

        // Handle existing photo with Cloudinary
        if ($operator->profile_photo_url && $operator->profile_photo_public_id) {
            $this->existing_photo_url = $operator->profile_photo_url;
            $this->existing_photo_public_id = $operator->profile_photo_public_id;
            $this->remove_photo = false;
        } else {
            $this->existing_photo_url = null;
            $this->existing_photo_public_id = null;
        }

        $this->action_text = 'Update';
        $this->editing = true;
        $this->show_password = false; // Don't show password fields when editing
    }

    /**
     * Load all roles for the dropdown
     */
    public function loadRoles()
    {
        $currentUser = currentUser();

        // Root can assign any role, admin can assign roles below them
        if ($currentUser && $currentUser->role->name === 'root') {
            $this->roles = Role::all();
        } elseif ($currentUser && $currentUser->role->name === 'admin') {
            // Admin can't create root users
            $this->roles = Role::where('name', '!=', 'root')->get();
        } else {
            // Other users can only create operators
            $this->roles = Role::where('name', 'operator')->get();
        }
    }

    /**
     * Load all regions for selection
     */
    public function loadRegions()
    {
        $currentUser = currentUser();

        // If admin or root, get all regions, otherwise get user's regions
        if ($currentUser && ($currentUser->role->name === 'admin' || $currentUser->role->name === 'root')) {
            $this->regions = Region::orderBy('name')->get();
        } else {
            // Get regions from user's districts
            $userDistrictIds = $currentUser->districts->pluck('id');
            $this->regions = Region::whereHas('districts', function ($query) use ($userDistrictIds) {
                $query->whereIn('id', $userDistrictIds);
            })->orderBy('name')->get();
        }
    }

    /**
     * Load districts based on selected region - CHANGED: Clear single district
     */
    public function loadDistrictsByRegion()
    {
        if (!$this->selected_region) {
            $this->districts = collect();
            $this->selected_district = null; // CHANGED: Clear single district
            return;
        }

        $currentUser = currentUser();

        // If admin or root, get all districts in region
        if ($currentUser && ($currentUser->role->name === 'admin' || $currentUser->role->name === 'root')) {
            $this->districts = District::where('region_id', $this->selected_region)
                ->orderBy('name')
                ->get();
        } else {
            // Get only user's districts within the selected region
            $userDistrictIds = $currentUser->districts->pluck('id');
            $this->districts = District::where('region_id', $this->selected_region)
                ->whereIn('id', $userDistrictIds)
                ->orderBy('name')
                ->get();
        }

        // Clear selected district if it doesn't belong to the new region - CHANGED
        $availableDistrictIds = $this->districts->pluck('id')->toArray();
        if ($this->selected_district && !in_array($this->selected_district, $availableDistrictIds)) {
            $this->selected_district = null;
        }
    }

    /**
     * Handle region selection change
     */
    public function updatedSelectedRegion()
    {
        $this->loadDistrictsByRegion();
    }

    // CHANGE 2: Add a computed property to show warning about multiple districts
    public function getHasMultipleDistrictsProperty()
    {
        if ($this->operator && $this->editing) {
            return $this->operator->districts->count() > 1;
        }
        return false;
    }

    // CHANGE 3: Add method to get all current districts for display
    public function getCurrentDistrictsProperty()
    {
        if ($this->operator && $this->editing) {
            return $this->operator->districts;
        }
        return collect();
    }

    /**
     * Toggle password visibility
     */
    public function togglePasswordVisibility()
    {
        $this->show_password = !$this->show_password;
    }

    /**
     * Reset the form to default values
     */
    public function resetForm()
    {
        $this->reset([
            'operator_id',
            'fname',
            'mname',
            'lname',
            'email',
            'phone',
            'role_id',
            'status',
            'profile_photo',
            'password',
            'password_confirmation',
            'selected_region',
            'selected_district', // CHANGED: Single district
            'editing',
            'existing_photo_url',
            'existing_photo_public_id',
            'remove_photo',
            'show_password'
        ]);

        // Set default values
        $this->action_text = 'Create';
        $this->status = 'active';
        $this->show_password = true; // Show password fields for new operators
        $this->selected_district = null; // CHANGED: Single district
    }

    /**
     * Load an existing operator for editing
     */
    public function loadOperator($operatorId)
    {
        $operator = User::findOrFail($operatorId);

        $this->resetForm();
        $this->loadOperatorData($operator);
    }

    /**
     * Mark existing photo for removal
     */
    public function removePhoto()
    {
        $this->remove_photo = true;
        $this->existing_photo_url = null;
    }

    /**
     * Get thumbnail URL for display
     */
    public function getThumbnailAttribute()
    {
        if ($this->existing_photo_public_id && !$this->remove_photo) {
            return $this->getThumbnailUrl($this->existing_photo_public_id, 150, 150);
        }
        return null;
    }

    /**
     * Create or update an operator
     */
    public function createOrUpdateOperator()
    {
        $rules = [
            'fname' => 'required|string|max:255',
            'mname' => 'nullable|string|max:255',
            'lname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->operator_id,
            'phone' => 'required|string|max:20|unique:users,phone,' . $this->operator_id,
            'role_id' => 'required|exists:roles,id',
            'status' => 'required|in:active,inactive,pending',
            'selected_region' => 'required|exists:regions,id',
            'selected_district' => 'required|exists:districts,id', // CHANGED: Single district validation
        ];

        // Add image validation rules
        $rules['profile_photo'] = $this->getImageValidationRules(false, ['max:5120']); // 5MB max

        // Add password rules for new operators or when updating password
        if (!$this->editing || ($this->editing && !empty($this->password))) {
            $rules['password'] = 'required|string|min:8|confirmed';
            $rules['password_confirmation'] = 'required';
        }

        $this->validate($rules);

        // Handle profile photo upload using the trait
        $imageData = null;
        if ($this->profile_photo) {
            $imageData = $this->handleImageUpload(
                $this->profile_photo,
                'operators',
                $this->existing_photo_public_id
            );

            if (!$imageData) {
                session()->flash('error', 'Failed to upload profile photo.');
                return;
            }
        } elseif ($this->remove_photo && $this->existing_photo_public_id) {
            // Delete the existing photo
            $this->handleImageDeletion($this->existing_photo_public_id);
            $imageData = ['url' => null, 'public_id' => null];
        }

        // Prepare data for create/update
        $data = [
            'fname' => $this->fname,
            'mname' => $this->mname,
            'lname' => $this->lname,
            'email' => $this->email,
            'phone' => $this->phone,
            'role_id' => $this->role_id,
            'status' => $this->status,
        ];

        // Add image data if processed
        if ($imageData) {
            $data['profile_photo_url'] = $imageData['url'];
            $data['profile_photo_public_id'] = $imageData['public_id'];
        }

        // Add password if provided
        if (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        // Create or update the operator
        $operator = User::updateOrCreate(
            ['id' => $this->operator_id],
            $data
        );

        if ($operator) {
            // Sync single district - CHANGED: Sync single district instead of array
            $operator->districts()->sync([$this->selected_district]);

            // Show success message
            session()->flash('saved', [
                'title' => 'Success',
                'text' => 'Operator ' . ($this->editing ? 'updated' : 'created') . ' successfully.',
            ]);

            // Handle different modes differently
            if ($this->mode === 'create') {
                // Reset form after saving for create mode
                $this->resetForm();
                $this->loadRoles();
                $this->loadRegions();
                // Redirect to operators list page
                return redirect('/operators');
            } else {
                // For edit mode, reload the operator data
                $this->loadOperatorData($operator);
            }
        } else {
            session()->flash('saved', [
                'title' => 'Error',
                'text' => 'Failed to ' . ($this->editing ? 'update' : 'create') . ' operator.',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.operator-form');
    }
}
