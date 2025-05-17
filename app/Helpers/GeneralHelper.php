<?php

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Check if the authenticated user is a root user.
 *
 * This function checks if the currently authenticated user has the role of root.
 *
 * @return bool
 */
if (! function_exists('isRoot')) {
    function isRoot() {
        $user = Auth::user();
        if ($user) {
            return $user->role_id == 1; // Assuming the role_id for root is 1
        }
        return false;
    }
}

/**
 * Retrieve the currently authenticated user as an instance of App\Models\User.
 *
 * This ensures you always have a fully-hydrated Eloquent model,
 * which is necessary for accessing relationships like districts().
 *
 * @return \App\Models\User|null
 */
if (! function_exists('currentUser')) {
    function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User
            ? $user
            : User::find($user?->id);
    }
}

/**
 * Get the district IDs associated with the currently authenticated user.
 *
 * This uses the `districts` relationship on the User model.
 *
 * @return \Illuminate\Support\Collection A collection of district IDs.
 */
if (! function_exists('currentDistrictIds')) {
    function currentDistrictIds(): Collection
    {
        return currentUser()?->districts()->pluck('districts.id') ?? collect();
    }
}

/**
 * Generate a default password for a new user.
 *
 * This function creates a default password based on the user's first name,
 * last name, and the current date. The password is formatted as:
 * "FirstNameLastNameYYYYMMDD".
 */
if (! function_exists('generateDefaultPassword')) {
    function generateDefaultPassword(string $firstName, string $lastName): string
    {
        return strtolower($firstName . $lastName . now()->format('Ymd'));
    }
}
