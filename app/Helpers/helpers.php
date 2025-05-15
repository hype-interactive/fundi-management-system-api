<?php

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
