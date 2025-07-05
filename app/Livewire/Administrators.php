<?php

namespace App\Livewire;

use App\Models\User;
use App\Traits\HasAlerts;
use Livewire\Component;
use App\Traits\LocationTrait;
use Illuminate\Support\Facades\Auth;

class Administrators extends Component
{
    use LocationTrait, HasAlerts;

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

    public $fname;
    public $mname;
    public $lname;
    public $email;
    public $phone;
    // public $password;
    // public $rpassword;

    public function mount()
    {
        $this->action_text = "Create";

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
        $this->reset(['fname', 'mname', 'lname', 'email', 'phone']);
        $this->action_text = "Create";
        $this->admin_id = null;
        $this->role = null;
        $this->role_name = null;
        $this->district = null;
        $this->district_name = null;
    }

    // Set admin details
    public function setAdmin($admin)
    {
        $this->action_text = "Update";

        $this->admin = User::with('districts', 'role')->find($admin['id']);

        $this->admin_id = $this->admin->id;
        $this->role = $this->admin->role_id;
        $this->fname = $this->admin->fname;
        $this->mname = $this->admin->mname;
        $this->lname = $this->admin->lname;
        $this->phone = $this->admin->phone;
        $this->email = $this->admin->email;
        $this->role_name = $this->admin->role?->name ?? 'Unknown';

        $firstDistrict = $this->admin->districts->first();

        $this->district = $firstDistrict?->id;
        $this->district_name = $firstDistrict?->name ?? 'NIL';
    }

    /**
     * Create or update an administrator.
     */
    public function createOrUpdateAdmin()
    {
        $this->validate([
            'fname' => 'required|string|max:255',
            'mname' => 'nullable|string|max:255',
            'lname' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $this->admin_id,
            'phone' => 'required|string|max:20',
            // 'password' => 'nullable|string|min:8|confirmed',
            'district' => 'required|exists:districts,id',
        ]);

        // Use the handleOperationWithFlash method for cleaner code
        $result = $this->handleOperationWithFlash(
            function () {
                $admin = User::updateOrCreate(
                    ['id' => $this->admin_id],
                    [
                        'fname' => $this->fname,
                        'mname' => $this->mname,
                        'lname' => $this->lname,
                        'email' => $this->email,
                        'phone' => $this->phone,
                        'role_id' => $this->role,
                        'status' => 'active',
                        // 'password' => bcrypt(generateDefaultPassword($this->fname, $this->lname)),
                        'password' => bcrypt('Fundi@2025'),
                    ]
                );

                if ($admin) {
                    $admin->districts()->sync([$this->district]);
                    return true;
                }
                return false;
            },
            'Success',
            'Administrator ' . strtolower($this->action_text) . 'd successfully.',
            'Error',
            'Failed to ' . strtolower($this->action_text) . ' administrator.'
        );

        if ($result) {
            // Reset form fields
            $this->reset(['fname', 'mname', 'lname', 'email', 'phone']);

            // Redirect to /administrators where the alert will be shown in mount()
            return redirect('/administrators');
        }
    }

    /**
     * Delete an administrator.
     */
    public function deleteAdmin($adminId)
    {
        $admin = User::find($adminId);

        if ($admin) {
            $admin->delete();
            session()->flash('message', "Administrator deleted successfully.");
        } else {
            session()->flash('error', "Failed to delete administrator.");
        }
    }

    public function render()
    {
        $this->districts = $this->getDistricts();
        $this->admins = User::where('role_id', "!=", 3)->orWhereNull('role_id')->get(); // Assuming 3 is the role_id for 'operator'

        return view('livewire.administrators');
    }
}
