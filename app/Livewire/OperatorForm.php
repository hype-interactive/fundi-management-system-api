<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Role;
use App\Models\District;
use App\Traits\HasImageUpload;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
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

    // Districts assignment
    public $selected_districts = [];
    public $available_districts = [];

    // UI state properties
    public $action_text = 'Create';
    public $editing = false;
    public $mode = 'create'; // 'create' or 'edit'
    public $show_password = false;

    // Available roles and districts
    public $roles = [];
    public $districts = [];

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
        $this->loadDistricts();
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

        // Load assigned districts
        $this->selected_districts = $operator->districts->pluck('id')->toArray();

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
     * Load all districts for assignment
     */
    public function loadDistricts()
    {
        $currentUser = currentUser();

        // If admin or root, get all districts, otherwise get user's districts
        if ($currentUser && ($currentUser->role->name === 'admin' || $currentUser->role->name === 'root')) {
            $this->districts = District::orderBy('name')->get();
        } else {
            $this->districts = $currentUser->districts;
        }
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
            'selected_districts',
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
        $this->selected_districts = [];
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
            'selected_districts' => 'required|array|min:1',
            'selected_districts.*' => 'exists:districts,id',
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
            // Sync districts
            $operator->districts()->sync($this->selected_districts);

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
                $this->loadDistricts();
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
