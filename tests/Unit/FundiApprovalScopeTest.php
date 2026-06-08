<?php

use App\Models\District;
use App\Models\Fundi;
use App\Models\Region;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

/**
 * Helper: create the Region→District→Ward hierarchy and return the ward.
 */
function createWard(): Ward
{
    $region = Region::create(['name' => 'Test Region']);
    $district = District::create(['region_id' => $region->id, 'name' => 'Test District']);

    return Ward::create(['district_id' => $district->id, 'name' => 'Test Ward']);
}

/**
 * Helper: create a user to use as approved_by / rejected_by.
 */
function createAdminUser(): User
{
    return User::factory()->create(['role_id' => 1]);
}

/**
 * Helper: build base fundi attributes without any approval state.
 */
function pendingFundiAttrs(Ward $ward, User $admin): array
{
    return [
        'unique_id' => 'FND'.rand(100000, 999999),
        'fname' => 'Test',
        'lname' => 'Fundi',
        'gender' => 'male',
        'primary_phone' => '+255700'.rand(100000, 999999),
        'transaction_phone' => '+255700'.rand(100000, 999999),
        'ward_id' => $ward->id,
        'ranking' => 'fundi_kuu',
        'status' => 'active',
        'created_by' => $admin->id,
        'approved_at' => null,
        'approved_by' => null,
        'rejected_at' => null,
        'rejected_by' => null,
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// scopePending
// ─────────────────────────────────────────────────────────────────────────────

test('scopePending returns fundi with no approval or rejection', function () {
    $ward = createWard();
    $admin = createAdminUser();
    Fundi::create(pendingFundiAttrs($ward, $admin));

    expect(Fundi::pending()->count())->toBe(1);
});

test('scopePending excludes fundi with only approved_at set', function () {
    $ward = createWard();
    $admin = createAdminUser();
    Fundi::create(array_merge(pendingFundiAttrs($ward, $admin), [
        'approved_at' => now(),
        'approved_by' => $admin->id,
    ]));

    expect(Fundi::pending()->count())->toBe(0);
});

test('scopePending excludes fundi with only rejected_at set', function () {
    $ward = createWard();
    $admin = createAdminUser();
    Fundi::create(array_merge(pendingFundiAttrs($ward, $admin), [
        'rejected_at' => now(),
        'rejected_by' => $admin->id,
    ]));

    expect(Fundi::pending()->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// scopeApproved
// ─────────────────────────────────────────────────────────────────────────────

test('scopeApproved returns fundi approved with no rejection', function () {
    $ward = createWard();
    $admin = createAdminUser();
    Fundi::create(array_merge(pendingFundiAttrs($ward, $admin), [
        'approved_at' => now(),
        'approved_by' => $admin->id,
    ]));

    expect(Fundi::approved()->count())->toBe(1);
});

test('scopeApproved excludes pending fundi', function () {
    $ward = createWard();
    $admin = createAdminUser();
    Fundi::create(pendingFundiAttrs($ward, $admin));

    expect(Fundi::approved()->count())->toBe(0);
});

test('scopeApproved excludes fundi that was approved then re-rejected', function () {
    $ward = createWard();
    $admin = createAdminUser();
    // rejected_at is more recent → fundi is effectively rejected
    Fundi::create(array_merge(pendingFundiAttrs($ward, $admin), [
        'approved_at' => now()->subDay(),
        'approved_by' => $admin->id,
        'rejected_at' => now(),
        'rejected_by' => $admin->id,
    ]));

    expect(Fundi::approved()->count())->toBe(0);
});

test('scopeApproved includes fundi that was rejected then re-approved', function () {
    $ward = createWard();
    $admin = createAdminUser();
    // approved_at is more recent → fundi is effectively approved
    Fundi::create(array_merge(pendingFundiAttrs($ward, $admin), [
        'rejected_at' => now()->subDay(),
        'rejected_by' => $admin->id,
        'approved_at' => now(),
        'approved_by' => $admin->id,
    ]));

    expect(Fundi::approved()->count())->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// scopeRejected
// ─────────────────────────────────────────────────────────────────────────────

test('scopeRejected returns fundi rejected with no approval', function () {
    $ward = createWard();
    $admin = createAdminUser();
    Fundi::create(array_merge(pendingFundiAttrs($ward, $admin), [
        'rejected_at' => now(),
        'rejected_by' => $admin->id,
    ]));

    expect(Fundi::rejected()->count())->toBe(1);
});

test('scopeRejected excludes pending fundi', function () {
    $ward = createWard();
    $admin = createAdminUser();
    Fundi::create(pendingFundiAttrs($ward, $admin));

    expect(Fundi::rejected()->count())->toBe(0);
});

test('scopeRejected includes fundi that was approved then re-rejected', function () {
    $ward = createWard();
    $admin = createAdminUser();
    Fundi::create(array_merge(pendingFundiAttrs($ward, $admin), [
        'approved_at' => now()->subDay(),
        'approved_by' => $admin->id,
        'rejected_at' => now(),
        'rejected_by' => $admin->id,
    ]));

    expect(Fundi::rejected()->count())->toBe(1);
});

test('scopeRejected excludes fundi that was rejected then re-approved', function () {
    $ward = createWard();
    $admin = createAdminUser();
    Fundi::create(array_merge(pendingFundiAttrs($ward, $admin), [
        'rejected_at' => now()->subDay(),
        'rejected_by' => $admin->id,
        'approved_at' => now(),
        'approved_by' => $admin->id,
    ]));

    expect(Fundi::rejected()->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// Mutual exclusivity
// ─────────────────────────────────────────────────────────────────────────────

test('approved and rejected scopes are mutually exclusive for any single fundi', function () {
    $ward = createWard();
    $admin = createAdminUser();

    // Re-rejected
    Fundi::create(array_merge(pendingFundiAttrs($ward, $admin), [
        'approved_at' => now()->subDay(),
        'approved_by' => $admin->id,
        'rejected_at' => now(),
        'rejected_by' => $admin->id,
        'unique_id' => 'FND111111',
        'primary_phone' => '+2557001111111',
        'transaction_phone' => '+2557001111112',
    ]));

    // Re-approved
    Fundi::create(array_merge(pendingFundiAttrs($ward, $admin), [
        'rejected_at' => now()->subDay(),
        'rejected_by' => $admin->id,
        'approved_at' => now(),
        'approved_by' => $admin->id,
        'unique_id' => 'FND222222',
        'primary_phone' => '+2557002222221',
        'transaction_phone' => '+2557002222222',
    ]));

    expect(Fundi::approved()->count())->toBe(1)
        ->and(Fundi::rejected()->count())->toBe(1)
        ->and(Fundi::pending()->count())->toBe(0);
});
