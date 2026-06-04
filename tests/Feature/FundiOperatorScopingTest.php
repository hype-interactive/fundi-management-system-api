<?php

use App\Models\District;
use App\Models\Fundi;
use App\Models\Region;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;

// Prevent QR code writes to real disk on every fundi create
beforeEach(fn () => Storage::fake('public'));

/**
 * Regression tests for operator district scoping on GET /api/v1/fundis.
 *
 * Cases under test:
 *   1. Operator A cannot see fundis whose ward belongs to Operator B's district
 *   2. Root user (role_id=1, no districts assigned) bypasses district filter and sees all fundis
 *   3. Operator with no district assignments receives an empty result set
 */

// ─── helpers ────────────────────────────────────────────────────────────────

function osGeo(): array
{
    $region = Region::create(['name' => 'OS Region '.uniqid()]);
    $dA = District::create(['region_id' => $region->id, 'name' => 'OS District A '.uniqid()]);
    $dB = District::create(['region_id' => $region->id, 'name' => 'OS District B '.uniqid()]);
    $wA = Ward::create(['district_id' => $dA->id, 'name' => 'OS Ward A']);
    $wB = Ward::create(['district_id' => $dB->id, 'name' => 'OS Ward B']);

    return compact('dA', 'dB', 'wA', 'wB');
}

function osOp(District $district): User
{
    $op = User::factory()->create(['role_id' => 2, 'status' => 'active']);
    $op->districts()->attach($district->id);

    return $op;
}

function osFundi(Ward $ward, User $creator, string $uniqueId, string $phone, string $txPhone): Fundi
{
    return Fundi::create([
        'unique_id' => $uniqueId,
        'fname' => 'OS',
        'lname' => 'Fundi',
        'gender' => 'male',
        'primary_phone' => $phone,
        'transaction_phone' => $txPhone,
        'nida_number' => null,
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

// ─── Test 1: operator isolation ───────────────────────────────────────────

test('operator cannot retrieve fundis from another operators districts', function () {
    ['dA' => $dA, 'dB' => $dB, 'wA' => $wA, 'wB' => $wB] = osGeo();
    $opA = osOp($dA);
    $opB = osOp($dB);

    $fundiA = osFundi($wA, $opA, 'FND901001', '+25570901001', '+25570902001');
    $fundiB = osFundi($wB, $opB, 'FND901002', '+25570901002', '+25570902002');

    Passport::actingAs($opA);
    $response = $this->getJson('/api/v1/fundis');

    $response->assertStatus(200);
    $uniqueIds = collect($response->json('data.fundis'))->pluck('unique_id');
    expect($uniqueIds)->toContain($fundiA->unique_id)
        ->not->toContain($fundiB->unique_id);
});

// ─── Test 2: root user bypasses district filter ───────────────────────────

test('root user can retrieve fundis across all districts', function () {
    ['dA' => $dA, 'dB' => $dB, 'wA' => $wA, 'wB' => $wB] = osGeo();
    $opA = osOp($dA);
    $opB = osOp($dB);

    $fundiA = osFundi($wA, $opA, 'FND902001', '+25570903001', '+25570904001');
    $fundiB = osFundi($wB, $opB, 'FND902002', '+25570903002', '+25570904002');

    // Root user with NO district assignments — should still see all fundis
    $root = User::factory()->create(['role_id' => 1, 'status' => 'active']);

    Passport::actingAs($root);
    $response = $this->getJson('/api/v1/fundis');

    $response->assertStatus(200);
    $uniqueIds = collect($response->json('data.fundis'))->pluck('unique_id');
    expect($uniqueIds)->toContain($fundiA->unique_id)
        ->toContain($fundiB->unique_id);
});

// ─── Test 3: operator with no districts sees nothing ─────────────────────

test('operator with no assigned districts receives empty result', function () {
    ['dA' => $dA, 'wA' => $wA] = osGeo();
    $opA = osOp($dA);
    osFundi($wA, $opA, 'FND903001', '+25570905001', '+25570906001');

    // Operator with no district assignments
    $emptyOp = User::factory()->create(['role_id' => 2, 'status' => 'active']);

    Passport::actingAs($emptyOp);
    $response = $this->getJson('/api/v1/fundis');

    $response->assertStatus(200);
    expect($response->json('data.fundis'))->toBeEmpty();
});
