<?php

namespace App\Livewire;

use App\Models\User;
use App\Traits\HasAlerts;
use App\Traits\HasUserNotifications;
use App\Traits\LocationTrait;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Administrators extends Component
{
    use HasAlerts, HasUserNotifications, LocationTrait;

    public $districts;

    public $district;

    public $district_name;

    public $role;

    public $role_name;

    public $is_root;

    public $action_text;

    public $admins;

    public $admin;

    public $admin_id;

    public $password = '';

    public $password_confirmation = '';

    public $fname;

    public $mname;

    public $lname;

    public $email;

    public $phone;

    protected $listeners = [
        'executeDeleteAdmin' => 'executeDeleteAdmin',
    ];

    public function mount()
    {
        $this->action_text = 'Create';

        $this->admin = User::with('districts', 'role')->find(Auth::id());

        $this->is_root = $this->admin->isRoot();
        $this->role = $this->admin->role_id;
        $this->role_name = $this->admin->role->name;

        $firstDistrict = $this->admin->districts->first();

        $this->district = $firstDistrict?->id;
        $this->district_name = $firstDistrict?->name ?? 'NIL';

        // Display any flashed alerts
        $this->displayFlashedAlerts();
    }

    /**
     * Refresh
     */
    public function refresh()
    {
        $this->resetValidation();
        $this->reset(['fname', 'mname', 'lname', 'email', 'phone', 'password', 'password_confirmation']);
        $this->action_text = 'Create';
        $this->admin_id = null;
        $this->role = null;
        $this->role_name = null;
        $this->district = null;
        $this->district_name = null;
    }

    /**
     * Set admin details for editing
     */
    public function setAdmin($admin)
    {
        $this->resetValidation();
        $this->password = '';
        $this->password_confirmation = '';
        $this->action_text = 'Update';

        $this->admin = User::with('districts', 'role')->find($admin['id']);

        $this->admin_id = $this->admin->id;
        $this->role = $this->admin->role_id;
        $this->fname = $this->admin->fname;
        $this->mname = $this->admin->mname;
        $this->lname = $this->admin->lname;
        $this->phone = $this->admin->phone;
        $this->email = $this->admin->email;
        $this->role_name = $this->admin->role?->name ?? 'Unknown';

        // $firstDistrict = $this->admin->districts->first();

        // $this->district = $firstDistrict?->id;
        // $this->district_name = $firstDistrict?->name ?? 'NIL';
    }

    /**
     * Create or update an administrator.
     */
    public function createOrUpdateAdmin()
    {
        $isNewUser = ! $this->admin_id;

        $this->validate([
            'fname' => 'required|string|max:255',
            'mname' => 'nullable|string|max:255',
            'lname' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$this->admin_id,
            'phone' => 'required|string|max:20',
            'password' => $isNewUser ? 'required|string|min:6|confirmed' : 'nullable|string|min:6|confirmed',
        ]);

        $result = $this->handleOperationWithFlash(
            function () use ($isNewUser) {
                $data = [
                    'fname' => $this->fname,
                    'mname' => $this->mname,
                    'lname' => $this->lname,
                    'email' => $this->email,
                    'phone' => $this->formatTanzanianNumber($this->phone),
                    'role_id' => $this->role,
                    'status' => 'active',
                ];

                if ($isNewUser) {
                    $data['password'] = $this->password;
                }

                $admin = User::updateOrCreate(['id' => $this->admin_id], $data);

                if (! $isNewUser && ! empty($this->password)) {
                    $admin->password = $this->password;
                    $admin->save();
                }

                return (bool) $admin;
            },
            'Success',
            'Administrator '.strtolower($this->action_text).'d successfully.',
            'Error',
            'Failed to '.strtolower($this->action_text).' administrator.'
        );

        if ($result) {
            $this->reset(['fname', 'mname', 'lname', 'email', 'phone', 'password', 'password_confirmation']);

            return redirect('/administrators');
        }
    }

    /**
     * Delete an administrator - Show confirmation
     */
    public function deleteAdmin($adminId)
    {
        // Find the admin to show their name in the confirmation
        $admin = User::find($adminId);

        if (! $admin) {
            $this->showErrorAlert('Error', 'Administrator not found');

            return;
        }

        $this->showDeleteConfirmation(
            'executeDeleteAdmin',
            ['adminId' => $adminId],
            'Delete Administrator?',
            "Are you sure you want to delete {$admin->full_name}? This action cannot be undone."
        );
    }

    /**
     * Execute delete - Called after confirmation
     */
    public function executeDeleteAdmin($adminId)
    {
        $result = $this->handleOperation(
            function () use ($adminId) {
                $admin = User::find($adminId);
                if ($admin) {
                    return $admin->delete();
                }

                return false;
            },
            'Deleted!',
            'Administrator deleted successfully',
            'Error',
            'Failed to delete administrator',
            true // Use toast
        );

        // Optionally refresh the list after deletion
        if ($result) {
            $this->admins = User::where('role_id', '!=', 3)
                ->orWhereNull('role_id')
                ->get();
        }
    }

    public function render()
    {
        $this->districts = $this->getDistricts();

        // Load administrators excluding operators (role_id = 3)
        $this->admins = User::where('role_id', '!=', 3)
            ->orWhereNull('role_id')
            ->get();

        return view('livewire.administrators');
    }

    public static function formatTanzanianNumber($phoneNumber)
    {
        // Remove all non-digit characters
        $digitsOnly = preg_replace('/\D/', '', $phoneNumber);

        // Check if we have at least 9 digits
        if (strlen($digitsOnly) < 9) {
            return null; // or throw an exception
        }

        // Take the last 9 digits
        $lastNineDigits = substr($digitsOnly, -9);

        // Format with +255 prefix
        return '+255'.$lastNineDigits;
    }
}
