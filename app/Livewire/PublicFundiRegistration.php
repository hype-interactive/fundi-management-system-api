<?php

namespace App\Livewire;

use Exception;
use App\Models\Fundi;
use Livewire\Component;
use App\Traits\HasAlerts;
use Livewire\WithFileUploads;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Log;
use App\Services\CloudinaryImageService;
use Illuminate\Support\Facades\RateLimiter;

#[Layout('layouts.guest')]
#[Title('Fundi Registration')]
class PublicFundiRegistration extends Component
{
    use WithFileUploads, HasAlerts;

    // PIN Authentication
    public $pin = '';
    public $is_authenticated = false;
    public $pin_attempts = 0;
    public $max_pin_attempts = 5;

    // Registration PIN (store in .env as FUNDI_REGISTRATION_PIN)
    protected $registration_pin;

    // Fundi form properties
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
    public $profile_photo_url = null;
    public $profile_photo_public_id = null;
    public $legacy_match = false;
    public $legacy_id = '';

    // UI state
    public $saving = false;
    public $registration_complete = false;
    public $registered_fundi_id = null;

    // Dropdowns
    public $districts = [];
    public $wards = [];

    // Inject CloudinaryImageService
    protected $cloudinaryService;

    public function boot()
    {
        $this->cloudinaryService = app(CloudinaryImageService::class);
        $this->registration_pin = config('app.fundi_registration_pin', '1234');
    }

    public function mount()
    {
        // Check if already authenticated in session
        if (session()->has('fundi_registration_authenticated')) {
            $this->is_authenticated = true;
        }

        $this->loadDistricts();
    }

    /**
     * Verify PIN/Password
     */
    public function verifyPin()
    {
        // Log the attempt
        Log::info('PIN verification attempt', [
            'ip' => request()->ip(),
            'pin_length' => strlen($this->pin),
            'config_pin' => config('app.fundi_registration_pin', 'NOT_SET')
        ]);

        // Rate limiting to prevent brute force
        $key = 'fundi-registration:' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, $this->max_pin_attempts)) {
            $seconds = RateLimiter::availableIn($key);
            $this->showErrorAlert(
                'Too Many Attempts',
                "Too many failed attempts. Please try again in " . ceil($seconds / 60) . " minutes."
            );
            return;
        }

        $this->validate([
            'pin' => 'required|string|min:4'
        ]);

        // Check if PIN matches
        if ($this->pin === $this->registration_pin) {
            $this->is_authenticated = true;
            session()->put('fundi_registration_authenticated', true);
            session()->put('fundi_registration_authenticated_at', now());

            RateLimiter::clear($key);

            $this->showSuccessAlert('Access Granted', 'You can now register a new fundi.');
            $this->pin = ''; // Clear PIN for security
        } else {
            RateLimiter::hit($key, 300); // 5 minutes lockout
            $this->pin_attempts++;

            $remaining = $this->max_pin_attempts - RateLimiter::attempts($key);

            $this->showErrorAlert(
                'Invalid PIN',
                "Incorrect PIN. You have $remaining attempts remaining."
            );
            $this->pin = '';
        }
    }

    /**
     * Load all districts for the dropdown
     */
    public function loadDistricts()
    {
        $this->districts = \App\Models\District::orderBy('name')->get();
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
    }

    /**
     * Watch for district changes to update wards
     * This is triggered automatically when $district property changes
     */
    public function updatedDistrict($value)
    {
        // Clear ward selection when district changes
        $this->ward_id = null;

        // Load wards for the new district
        $this->loadWards();

        // Optional: Show feedback to user
        if ($value && count($this->wards) > 0) {
            Log::info('District changed, wards loaded', [
                'district_id' => $value,
                'wards_count' => count($this->wards)
            ]);
        }
    }

    /**
     * Optional: Watch for ward changes (for debugging or additional logic)
     */
    public function updatedWardId($value)
    {
        if ($value) {
            Log::info('Ward selected', [
                'ward_id' => $value,
                'district_id' => $this->district
            ]);
        }
    }

    /**
     * Optional: Real-time validation for phone numbers
     */
    public function updatedPrimaryPhone($value)
    {
        // Remove any non-numeric characters
        $this->primary_phone = preg_replace('/[^0-9]/', '', $value);

        // Optional: Validate format
        if (strlen($this->primary_phone) > 0 && strlen($this->primary_phone) < 9) {
            $this->addError('primary_phone', 'Phone number should be 9 digits (e.g., 712345678)');
        } else {
            $this->resetErrorBag('primary_phone');
        }
    }

    /**
     * Optional: Real-time validation for transaction phone
     */
    public function updatedTransactionPhone($value)
    {
        // Remove any non-numeric characters
        $this->transaction_phone = preg_replace('/[^0-9]/', '', $value);

        // Optional: Validate format
        if (strlen($this->transaction_phone) > 0 && strlen($this->transaction_phone) < 9) {
            $this->addError('transaction_phone', 'Phone number should be 9 digits (e.g., 612345678)');
        } else {
            $this->resetErrorBag('transaction_phone');
        }
    }

    /**
     * Optional: Watch for legacy_match toggle
     */
    public function updatedLegacyMatch($value)
    {
        if (!$value) {
            // Clear legacy_id when unchecked
            $this->legacy_id = '';
            $this->resetErrorBag('legacy_id');
        }
    }

    /**
     * Optional: Real-time field validation
     */
    public function updated($propertyName)
    {
        // Validate specific fields in real-time
        $validationRules = [
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'nida_number' => 'nullable|string|max:20',
            'primary_phone' => 'required|string|min:9|max:20|unique:fundis,primary_phone',
            'transaction_phone' => 'nullable|string|min:9|max:20',
            'district' => 'required|exists:districts,id',
            'ward_id' => 'required|exists:wards,id',
            'ranking' => 'required|in:fundi_kuu,fundi_msaidizi',
            'legacy_id' => 'nullable|required_if:legacy_match,true|string|max:255',
        ];

        // Only validate if the field has a validation rule
        if (array_key_exists($propertyName, $validationRules)) {
            $this->validateOnly($propertyName, [
                $propertyName => $validationRules[$propertyName]
            ]);
        }
    }

    /**
     * Register new fundi
     */
    public function registerFundi()
    {
        if (!$this->is_authenticated) {
            $this->showErrorAlert('Unauthorized', 'Please authenticate first.');
            return;
        }

        $this->saving = true;

        // Validation rules
        $rules = [
            'fname' => 'required|string|max:255',
            'mname' => 'nullable|string|max:255',
            'lname' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'nida_number' => 'nullable|string|max:255',
            'primary_phone' => 'required|string|max:20|unique:fundis,primary_phone',
            'transaction_phone' => 'nullable|string|max:20',
            'district' => 'required|exists:districts,id',
            'ward_id' => 'required|exists:wards,id',
            'ranking' => 'required|in:fundi_kuu,fundi_msaidizi',
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

        try {
            // Handle photo upload if provided
            $photo_url = null;
            $photo_public_id = null;

            if ($this->profile_photo) {
                $photoResult = $this->handlePhotoUpload();
                if ($photoResult) {
                    $photo_url = $photoResult['url'];
                    $photo_public_id = $photoResult['public_id'];
                }
            }

            // Create fundi with status 'pending' for review
            $fundi = Fundi::create([
                'unique_id' => 'F' . date('Ymd') . rand(1000, 9999),
                'fname' => $this->fname,
                'mname' => $this->mname,
                'lname' => $this->lname,
                'gender' => $this->gender,
                'nida_number' => !empty($this->nida_number) ? $this->nida_number : null,
                'primary_phone' => $this->formatPhoneNumber($this->primary_phone),
                'transaction_phone' => $this->transaction_phone ? $this->formatPhoneNumber($this->transaction_phone) : null,
                'ward_id' => $this->ward_id,
                'ranking' => $this->ranking,
                'profile_photo_url' => $photo_url,
                'profile_photo_public_id' => $photo_public_id,
                'status' => 'pending', // Always pending for public registration
                'legacy_match' => $this->legacy_match,
                'legacy_id' => $this->legacy_match ? $this->legacy_id : null,
                'created_by' => 1, // System user ID
            ]);

            $this->registered_fundi_id = $fundi->id;
            $this->registration_complete = true;

            $this->showSuccessAlert(
                'Registration Successful!',
                'Fundi registered successfully. Reference ID: ' . $fundi->unique_id
            );

            Log::info('Public fundi registration completed', [
                'fundi_id' => $fundi->id,
                'unique_id' => $fundi->unique_id,
                'ip' => request()->ip(),
                'district_id' => $this->district,
                'ward_id' => $this->ward_id
            ]);
        } catch (Exception $e) {
            Log::error('Public fundi registration failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $this->showErrorAlert('Registration Failed', 'An error occurred. Please try again.');
        } finally {
            $this->saving = false;
        }
    }

    /**
     * Handle photo upload
     */
    private function handlePhotoUpload()
    {
        try {
            if (!$this->cloudinaryService->testConnection()) {
                throw new Exception('Unable to connect to image storage service.');
            }

            $uploadResult = $this->cloudinaryService->upload(
                $this->profile_photo,
                'FundiApp/PublicRegistrations'
            );

            if ($uploadResult && isset($uploadResult['url']) && isset($uploadResult['public_id'])) {
                return [
                    'url' => $uploadResult['url'],
                    'public_id' => $uploadResult['public_id']
                ];
            }

            return null;
        } catch (Exception $e) {
            Log::error('Photo upload exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Reset form for new registration
     */
    public function registerAnother()
    {
        $this->reset([
            'fname',
            'mname',
            'lname',
            'gender',
            'nida_number',
            'primary_phone',
            'transaction_phone',
            'district',
            'ward_id',
            'ranking',
            'profile_photo',
            'profile_photo_url',
            'profile_photo_public_id',
            'legacy_match',
            'legacy_id',
            'registration_complete',
            'registered_fundi_id'
        ]);

        $this->wards = [];
        $this->resetErrorBag(); // Clear all validation errors
        $this->showInfoAlert('Form Reset', 'You can register another fundi.');
    }

    /**
     * Logout from registration
     */
    public function logout()
    {
        session()->forget('fundi_registration_authenticated');
        session()->forget('fundi_registration_authenticated_at');
        $this->is_authenticated = false;
        $this->reset();
        $this->wards = [];
        $this->resetErrorBag();
        $this->showInfoAlert('Logged Out', 'You have been logged out.');
    }

    public function render()
    {
        return view('livewire.public-fundi-registration');
    }

    /**
     * Format phone number to include +255 prefix
     *
     * @param string $phone
     * @return string
     */
    private function formatPhoneNumber($phone)
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove leading zero if present
        $phone = ltrim($phone, '0');

        // If it already starts with 255, add the + prefix
        if (substr($phone, 0, 3) === '255') {
            return '+' . $phone;
        }

        // Otherwise, prepend +255
        return '+255' . $phone;
    }
}
