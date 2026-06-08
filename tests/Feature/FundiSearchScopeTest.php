<?php

use App\Models\District;
use App\Models\Fundi;
use App\Models\Region;
use App\Models\User;
use App\Models\Ward;
use Laravel\Passport\Passport;

/**
 * Regression tests for FundiController::searchForSiteVisit() operator scoping.
 *
 * Bug: buildSearchResultsData() had no operator district filter, so searching
 * without a ward_id returned fundis from ALL operators' districts.
 * Fix: add ->whereHas('ward.district', fn($q) => $q->whereIn('id', $this->getOperatorDistricts()))
 * to the base query in buildSearchResultsData().
 */
function searchGeoSetup(): array
{
    $region = Region::create(['name' => 'Search Region']);
    $districtA = District::create(['region_id' => $region->id, 'name' => 'District A']);
    $districtB = District::create(['region_id' => $region->id, 'name' => 'District B']);
    $wardA = Ward::create(['district_id' => $districtA->id, 'name' => 'Ward A']);
    $wardB = Ward::create(['district_id' => $districtB->id, 'name' => 'Ward B']);

    return compact('districtA', 'districtB', 'wardA', 'wardB');
}

function searchOperator(District $district): User
{
    $op = User::factory()->create(['role_id' => 2, 'status' => 'active']);
    $op->districts()->attach($district->id);

    return $op;
}

function searchFundi(Ward $ward, User $creator, string $name, string $suffix = ''): Fundi
{
    return Fundi::create([
        'unique_id' => 'FND'.rand(100000, 999999).$suffix,
        'fname' => $name,
        'lname' => 'Fundi',
        'gender' => 'male',
        'primary_phone' => '+255700'.rand(100000, 999999),
        'transaction_phone' => '+255700'.rand(100000, 999999),
        'ward_id' => $ward->id,
        'ranking' => 'fundi_kuu',
        'status' => 'active',
        'created_by' => $creator->id,
        'approved_at' => null,
        'approved_by' => null,
        'rejected_at' => null,
        'rejected_by' => null,
    ]);
}

test('search without ward_id returns only fundis from the operators own districts', function () {
    ['districtA' => $districtA, 'districtB' => $districtB, 'wardA' => $wardA, 'wardB' => $wardB]
        = searchGeoSetup();

    $operatorA = searchOperator($districtA);
    $operatorB = searchOperator($districtB);

    // "Alice" belongs to Operator A's district
    searchFundi($wardA, $operatorA, 'Alice');
    // "Alice" duplicate belongs to Operator B's district — Operator A must not see it
    searchFundi($wardB, $operatorB, 'Alice');

    Passport::actingAs($operatorA);
    $response = $this->getJson('/api/v1/fundis/search?query=Alice');

    $response->assertOk();

    $results = $response->json('data');
    expect(count($results))->toBe(1, 'Operator A should see exactly 1 result — only their district\'s fundi');

    $wardIds = collect($results)->pluck('ward_name')->all();
    // All results must come from Ward A (Operator A's ward), not Ward B
    foreach ($results as $result) {
        expect($result['ward_name'])->toBe('Ward A');
    }
});

test('search with valid ward_id still respects operator district boundary', function () {
    ['districtA' => $districtA, 'districtB' => $districtB, 'wardA' => $wardA, 'wardB' => $wardB]
        = searchGeoSetup();

    $operatorA = searchOperator($districtA);
    $operatorB = searchOperator($districtB);

    searchFundi($wardB, $operatorB, 'Bob');

    // Attempting to search in ward B (belongs to Operator B) while acting as Operator A
    Passport::actingAs($operatorA);
    $response = $this->getJson('/api/v1/fundis/search?query=Bob&ward_id='.$wardB->id);

    // Should be rejected as unauthorized (ward not in operator's districts)
    $response->assertForbidden();
});

test('search without ward_id returns fundi from operators own district', function () {
    ['districtA' => $districtA, 'wardA' => $wardA] = searchGeoSetup();

    $operatorA = searchOperator($districtA);
    searchFundi($wardA, $operatorA, 'Charlie');

    Passport::actingAs($operatorA);
    $response = $this->getJson('/api/v1/fundis/search?query=Charlie');

    $response->assertOk();
    expect(count($response->json('data')))->toBe(1);
});
