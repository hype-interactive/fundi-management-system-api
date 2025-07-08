<?php

namespace App\Livewire;

use App\Models\Role;
use App\Models\User;
use App\Models\Region;
use Livewire\Component;
use App\Models\District;
use Livewire\WithFileUploads;
use App\Traits\HasImageUpload;
use App\Traits\HasUserNotifications; // ADD NOTIFICATION TRAIT
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class OperatorForm extends Component
{
    use WithFileUploads, HasImageUpload, HasUserNotifications; // ADD NOTIFICATION TRAIT

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

    // Region and District assignment
    public $selected_region = null;
    public $selected_district = null;
    public $available_districts = [];

    // UI state properties
    public $action_text = 'Create';
    public $editing = false;
    public $mode = 'create'; // 'create' or 'edit'
    public $show_password = false;

    // NEW: Password management properties
    public $use_default_password = false;
    public $password_changed = false;

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

        // Load assigned districts and determine region
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

        // Handle existing photo
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
        $this->show_password = false; // Don't show password fields when editing initially
        $this->use_default_password = false;
        $this->password_changed = false;
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
     * Load districts based on selected region
     */
    public function loadDistrictsByRegion()
    {
        if (!$this->selected_region) {
            $this->districts = collect();
            $this->selected_district = null;
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

    /**
     * NEW: Generate secure default password
     */
    private function generateDefaultPassword()
    {
        return 'Operator@' . date('Y') . rand(100, 999);
    }

    /**
     * NEW: Toggle between manual and default password
     */
    public function toggleDefaultPassword()
    {
        $this->use_default_password = !$this->use_default_password;

        if ($this->use_default_password) {
            // Clear manual password fields when using default
            $this->password = '';
            $this->password_confirmation = '';
        }

        // Reset validation errors
        $this->resetErrorBag(['password', 'password_confirmation']);
    }

    /**
     * NEW: Show/hide password change section for existing operators
     */
    public function togglePasswordChange()
    {
        $this->show_password = !$this->show_password;
        $this->password_changed = $this->show_password;

        if (!$this->show_password) {
            // Clear password fields when hiding
            $this->password = '';
            $this->password_confirmation = '';
            $this->use_default_password = false;
            $this->resetErrorBag(['password', 'password_confirmation']);
        }
    }

    // Computed properties
    public function getHasMultipleDistrictsProperty()
    {
        if ($this->operator && $this->editing) {
            return $this->operator->districts->count() > 1;
        }
        return false;
    }

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
            'selected_district',
            'editing',
            'existing_photo_url',
            'existing_photo_public_id',
            'remove_photo',
            'show_password',
            'use_default_password',
            'password_changed'
        ]);

        // Set default values for new operators
        $this->action_text = 'Create';
        $this->status = 'active';
        $this->show_password = true; // Always show password section for new operators
        $this->use_default_password = false; // Start with manual password entry
        $this->selected_district = null;
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
     * ENHANCED: Create or update an operator with notification support
     */
    public function createOrUpdateOperator()
    {
        // Basic validation rules
        $rules = [
            'fname' => 'required|string|max:255',
            'mname' => 'nullable|string|max:255',
            'lname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->operator_id,
            'phone' => 'required|string|max:20|unique:users,phone,' . $this->operator_id,
            'role_id' => 'required|exists:roles,id',
            'status' => 'required|in:active,inactive,pending',
            'selected_region' => 'required|exists:regions,id',
            'selected_district' => 'required|exists:districts,id',
        ];

        // Add image validation
        $rules['profile_photo'] = $this->getImageValidationRules(false, ['max:5120']);

        // ENHANCED PASSWORD LOGIC
        $isNewOperator = !$this->editing;
        $willSendNotifications = false;
        $passwordForNotification = null;

        if ($isNewOperator) {
            // NEW OPERATOR: Always require password (manual or default)
            if (!$this->use_default_password) {
                // Manual password - validate it
                $rules['password'] = 'required|string|min:8|confirmed';
                $rules['password_confirmation'] = 'required';
                $passwordForNotification = $this->password;
            } else {
                // Use default password - generate it
                $passwordForNotification = $this->generateDefaultPassword();
                $this->password = $passwordForNotification;
                $this->password_confirmation = $passwordForNotification;
            }
            $willSendNotifications = true;
        } else {
            // EXISTING OPERATOR: Only if password change is requested
            if ($this->show_password && $this->password_changed) {
                if (!$this->use_default_password) {
                    // Manual password change
                    $rules['password'] = 'required|string|min:8|confirmed';
                    $rules['password_confirmation'] = 'required';
                    $passwordForNotification = $this->password;
                } else {
                    // Generate new default password
                    $passwordForNotification = $this->generateDefaultPassword();
                    $this->password = $passwordForNotification;
                    $this->password_confirmation = $passwordForNotification;
                }
                $willSendNotifications = true;
            }
        }

        // Validate all rules
        $this->validate($rules);

        // Handle profile photo upload
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

        // Add password if provided or generated
        if (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        // Create or update the operator
        $operator = User::updateOrCreate(
            ['id' => $this->operator_id],
            $data
        );

        if ($operator) {
            // Sync district
            $operator->districts()->sync([$this->selected_district]);

            // SEND NOTIFICATIONS
            if ($willSendNotifications && $passwordForNotification) {
                // Load relationships for notification
                $operator->load('role', 'districts');

                if ($isNewOperator) {
                    // Send welcome credentials
                    $notificationSent = $this->sendNotificationWithFeedback(
                        $operator,
                        $passwordForNotification,
                        'login_credentials'
                    );

                    Log::info("New operator created: {$operator->id} - {$operator->email} - Credentials sent: " . ($notificationSent ? 'Yes' : 'No'));
                } else {
                    // Send password reset notification
                    $notificationSent = $this->sendNotificationWithFeedback(
                        $operator,
                        $passwordForNotification,
                        'password_reset'
                    );

                    Log::info("Password updated for operator: {$operator->id} - {$operator->email} - Notification sent: " . ($notificationSent ? 'Yes' : 'No'));
                }
            }

            // Success message
            $message = 'Operator ' . ($this->editing ? 'updated' : 'created') . ' successfully.';
            if ($willSendNotifications) {
                $passwordType = $this->use_default_password ? 'default' : 'custom';
                $message .= " Login credentials ({$passwordType} password) have been sent via SMS and email.";
            }

            session()->flash('saved', [
                'title' => 'Success',
                'text' => $message,
            ]);

            // Handle different modes
            if ($this->mode === 'create') {
                $this->resetForm();
                $this->loadRoles();
                $this->loadRegions();
                return redirect('/operators');
            } else {
                $this->loadOperatorData($operator);
                $this->password_changed = false;
                $this->show_password = false;
                $this->use_default_password = false;
            }
        } else {
            session()->flash('saved', [
                'title' => 'Error',
                'text' => 'Failed to ' . ($this->editing ? 'update' : 'create') . ' operator.',
            ]);
        }
    }

    /**
     * NEW: Force password reset for existing operator
     */
    public function forcePasswordReset()
    {
        if (!$this->operator_id) {
            session()->flash('error', 'No operator selected.');
            return;
        }

        $operator = User::with('role', 'districts')->find($this->operator_id);

        if (!$operator) {
            session()->flash('error', 'Operator not found.');
            return;
        }

        // Generate new password
        $newPassword = $this->generateDefaultPassword();

        // Update password in database
        $operator->update(['password' => Hash::make($newPassword)]);

        // Send notification
        $this->sendNotificationWithFeedback($operator, $newPassword, 'password_reset');

        session()->flash('message', 'Password has been reset and sent to the operator.');

        Log::info("Forced password reset for operator: {$operator->id} - {$operator->email}");
    }

    /**
     * NEW: Resend login credentials
     */
    public function resendCredentials()
    {
        if (!$this->operator_id) {
            session()->flash('error', 'No operator selected.');
            return;
        }

        $operator = User::with('role', 'districts')->find($this->operator_id);

        if (!$operator) {
            session()->flash('error', 'Operator not found.');
            return;
        }

        // Generate new password for security
        $newPassword = $this->generateDefaultPassword();

        // Update password
        $operator->update(['password' => Hash::make($newPassword)]);

        // Send credentials
        $this->sendNotificationWithFeedback($operator, $newPassword, 'login_credentials');

        session()->flash('message', 'New credentials generated and sent to the operator.');

        Log::info("Credentials resent for operator: {$operator->id} - {$operator->email}");
    }

    public function render()
    {
        return view('livewire.operator-form');
    }
}
