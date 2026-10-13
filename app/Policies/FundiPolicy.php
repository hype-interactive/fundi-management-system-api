<?php

namespace App\Policies;

use App\Models\Fundi;
use App\Models\User;

class FundiPolicy
{
    /**
     * Root and admin can manage every fundi.
     */
    public function before(User $user, string $ability): ?bool
    {
        if (in_array((int) $user->role_id, [1, 2], true)) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $this->isActive($user);
    }

    public function view(User $user, Fundi $fundi): bool
    {
        return $this->isActive($user) && $this->inAssignedDistrict($user, $fundi);
    }

    public function create(User $user): bool
    {
        return $this->isActive($user);
    }

    public function update(User $user, Fundi $fundi): bool
    {
        return $this->view($user, $fundi);
    }

    public function delete(User $user, Fundi $fundi): bool
    {
        return $this->view($user, $fundi);
    }

    private function isActive(User $user): bool
    {
        return ! isset($user->status) || $user->status === 'active';
    }

    private function inAssignedDistrict(User $user, Fundi $fundi): bool
    {
        $fundi->loadMissing('ward');
        $districtId = $fundi->ward?->district_id;

        if (! $districtId) {
            return false;
        }

        return $user->districts()->where('districts.id', $districtId)->exists();
    }
}
