<?php

use App\Models\District;
use App\Models\Fundi;
use App\Models\Region;
use App\Models\User;
use App\Models\Ward;
use Laravel\Passport\Passport;

/**
 * Regression tests for DashboardController's approved fundi count.
 *
 * Bug: 'approved' count used ->whereNotNull('approved_at') which incorrectly
 * includes fundis that were later re-rejected (rejected_at > approved_at).
 * Fix: replace with ->approved() scope.
 */
function dashboardGeoSetup(): array
{
    $region = Region::create(['name' => 'Dash Region']);
    $district = District::create(['region_id' => $region->id, 'name' => 'Dash District']);
    $ward = Ward::create(['district_id' => $district->id, 'name' => 'Dash Ward']);

    return compact('district', 'ward');
}

function dashboardOperator(District $district): User
{
    $operator = User::factory()->create(['role_id' => 2, 'status' => 'active']);
    $operator->districts()->attach($district->id);

    return $operator;
}

function dashboardFundiBase(Ward $ward, User $operator): array
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
        'created_by' => $operator->id,
        'approved_at' => null,
        'approved_by' => null,
        'rejected_at' => null,
        'rejected_by' => null,
    ];
}

test('dashboard approved count excludes fundi that was approved then re-rejected', function () {
    ['district' => $district, 'ward' => $ward] = dashboardGeoSetup();
    $operator = dashboardOperator($district);

    // This fundi was approved yesterday but rejected today — it is effectively REJECTED
    Fundi::create(array_merge(dashboardFundiBase($ward, $operator), [
        'approved_at' => now()->subDay(),
        'approved_by' => $operator->id,
        'rejected_at' => now(),
        'rejected_by' => $operator->id,
    ]));

    Passport::actingAs($operator);
    $response = $this->getJson('/api/v1/dashboard');

    $response->assertOk();

    // A re-rejected fundi must NOT appear in the approved count
    expect($response->json('data.stats.fundis.approved'))->toBe(0);
});

test('dashboard approved count includes fundi that was rejected then re-approved', function () {
    ['district' => $district, 'ward' => $ward] = dashboardGeoSetup();
    $operator = dashboardOperator($district);

    // This fundi was rejected yesterday but approved today — it is effectively APPROVED
    Fundi::create(array_merge(dashboardFundiBase($ward, $operator), [
        'rejected_at' => now()->subDay(),
        'rejected_by' => $operator->id,
        'approved_at' => now(),
        'approved_by' => $operator->id,
    ]));

    Passport::actingAs($operator);
    $response = $this->getJson('/api/v1/dashboard');

    $response->assertOk();

    expect($response->json('data.stats.fundis.approved'))->toBe(1);
});

test('dashboard returns 200 and correct structure', function () {
    ['district' => $district, 'ward' => $ward] = dashboardGeoSetup();
    $operator = dashboardOperator($district);

    Passport::actingAs($operator);
    $response = $this->getJson('/api/v1/dashboard');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'period' => ['from', 'to'],
                'stats' => [
                    'fundis' => ['total', 'approved', 'pending', 'active', 'registered_this_period'],
                ],
            ],
        ]);
});
