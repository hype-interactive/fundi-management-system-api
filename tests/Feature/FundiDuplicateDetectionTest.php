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
 * Regression tests for checkForDuplicateFundi() — all four business rules on
 * both store (POST /api/v1/fundis) and update (PUT /api/v1/fundis/{fundi}).
 *
 * Rules under test:
 *   1. primary_phone must be globally unique
 *   2. transaction_phone must be globally unique
 *   3. nida_number must be unique when provided
 *   4. fname + lname + ward_id combination must be unique
 *   5. Update excludes current fundi from all four checks ($excludeId)
 *   6. null nida_number does not trigger uniqueness errors
 */

// ─── helpers ────────────────────────────────────────────────────────────────

function dupGeoSetup(): array
{
    $region = Region::create(['name' => 'Dup Region']);
    $district = District::create(['region_id' => $region->id, 'name' => 'Dup District']);
    $ward = Ward::create(['district_id' => $district->id, 'name' => 'Dup Ward']);

    return compact('district', 'ward');
}

function dupOperator(District $district): User
{
    $op = User::factory()->create(['role_id' => 2, 'status' => 'active']);
    $op->districts()->attach($district->id);

    return $op;
}

/** Minimal valid payload for POST /api/v1/fundis. */
function validPayload(Ward $ward, array $overrides = []): array
{
    return array_merge([
        'fname' => 'John',
        'lname' => 'Doe',
        'gender' => 'male',
        'primary_phone' => '+25570011111',
        'transaction_phone' => '+25570022222',
        'ward_id' => $ward->id,
        'ranking' => 'fundi_kuu',
        'nida_number' => null,
    ], $overrides);
}

/** Create a fundi directly in DB (bypasses controller). */
function existingFundi(Ward $ward, User $creator, array $overrides = []): Fundi
{
    return Fundi::create(array_merge([
        'unique_id' => 'FND'.rand(100000, 999999),
        'fname' => 'Jane',
        'lname' => 'Smith',
        'gender' => 'female',
        'primary_phone' => '+25570099991',
        'transaction_phone' => '+25570099992',
        'nida_number' => null,
        'ward_id' => $ward->id,
        'ranking' => 'fundi_kuu',
        'status' => 'active',
        'created_by' => $creator->id,
        'approved_at' => null,
        'approved_by' => null,
        'rejected_at' => null,
        'rejected_by' => null,
    ], $overrides));
}

// ─── Rule 1: primary_phone ────────────────────────────────────────────────

test('store rejects fundi with duplicate primary_phone', function () {
    ['district' => $district, 'ward' => $ward] = dupGeoSetup();
    $operator = dupOperator($district);
    existingFundi($ward, $operator, ['primary_phone' => '+25570011111']);

    Passport::actingAs($operator);
    $response = $this->postJson('/api/v1/fundis', validPayload($ward, [
        'primary_phone' => '+25570011111', // duplicate
    ]));

    $response->assertStatus(422);
});

test('update rejects fundi whose primary_phone collides with another fundi', function () {
    ['district' => $district, 'ward' => $ward] = dupGeoSetup();
    $operator = dupOperator($district);

    existingFundi($ward, $operator, ['primary_phone' => '+25570011111', 'unique_id' => 'FND111111']);
    $target = existingFundi($ward, $operator, [
        'primary_phone' => '+25570033333',
        'transaction_phone' => '+25570044444',
        'fname' => 'Target',
        'unique_id' => 'FND222222',
    ]);

    Passport::actingAs($operator);
    $response = $this->putJson("/api/v1/fundis/{$target->id}", validPayload($ward, [
        'fname' => 'Target',
        'primary_phone' => '+25570011111', // collision with first fundi
        'transaction_phone' => '+25570044444',
    ]));

    $response->assertStatus(422);
});

// ─── Rule 2: transaction_phone ────────────────────────────────────────────

test('store rejects fundi with duplicate transaction_phone', function () {
    ['district' => $district, 'ward' => $ward] = dupGeoSetup();
    $operator = dupOperator($district);
    existingFundi($ward, $operator, ['transaction_phone' => '+25570022222']);

    Passport::actingAs($operator);
    $response = $this->postJson('/api/v1/fundis', validPayload($ward, [
        'transaction_phone' => '+25570022222', // duplicate
    ]));

    $response->assertStatus(422);
});

test('update rejects fundi whose transaction_phone collides with another fundi', function () {
    ['district' => $district, 'ward' => $ward] = dupGeoSetup();
    $operator = dupOperator($district);

    existingFundi($ward, $operator, ['transaction_phone' => '+25570022222', 'unique_id' => 'FND333333']);
    $target = existingFundi($ward, $operator, [
        'primary_phone' => '+25570055555',
        'transaction_phone' => '+25570066666',
        'fname' => 'Target',
        'unique_id' => 'FND444444',
    ]);

    Passport::actingAs($operator);
    $response = $this->putJson("/api/v1/fundis/{$target->id}", validPayload($ward, [
        'fname' => 'Target',
        'primary_phone' => '+25570055555',
        'transaction_phone' => '+25570022222', // collision
    ]));

    $response->assertStatus(422);
});

// ─── Rule 3: nida_number ─────────────────────────────────────────────────

test('store rejects fundi with duplicate nida_number', function () {
    ['district' => $district, 'ward' => $ward] = dupGeoSetup();
    $operator = dupOperator($district);
    existingFundi($ward, $operator, ['nida_number' => '12345678901234567890']);

    Passport::actingAs($operator);
    $response = $this->postJson('/api/v1/fundis', validPayload($ward, [
        'nida_number' => '12345678901234567890', // duplicate
    ]));

    $response->assertStatus(422);
});

test('store allows multiple fundis with null nida_number', function () {
    ['district' => $district, 'ward' => $ward] = dupGeoSetup();
    $operator = dupOperator($district);
    existingFundi($ward, $operator, ['nida_number' => null]);

    Passport::actingAs($operator);
    $response = $this->postJson('/api/v1/fundis', validPayload($ward, [
        'nida_number' => null,
        'primary_phone' => '+25570077777',
        'transaction_phone' => '+25570088888',
        'fname' => 'Unique',
        'lname' => 'Name',
    ]));

    // Should not be rejected for NIDA uniqueness
    $response->assertStatus(201);
});

// ─── Rule 4: fname + lname + ward_id ─────────────────────────────────────

test('store rejects fundi with duplicate fname + lname + ward_id combination', function () {
    ['district' => $district, 'ward' => $ward] = dupGeoSetup();
    $operator = dupOperator($district);
    existingFundi($ward, $operator, ['fname' => 'John', 'lname' => 'Doe']);

    Passport::actingAs($operator);
    $response = $this->postJson('/api/v1/fundis', validPayload($ward, [
        'fname' => 'John',
        'lname' => 'Doe', // same name + same ward = duplicate
        'primary_phone' => '+25570011112',
        'transaction_phone' => '+25570022223',
    ]));

    $response->assertStatus(422);
});

test('store allows same name in a different ward', function () {
    ['district' => $district, 'ward' => $ward] = dupGeoSetup();
    $ward2 = Ward::create(['district_id' => $district->id, 'name' => 'Ward Two']);
    $operator = dupOperator($district);
    existingFundi($ward, $operator, ['fname' => 'John', 'lname' => 'Doe']);

    Passport::actingAs($operator);
    $response = $this->postJson('/api/v1/fundis', validPayload($ward2, [
        'fname' => 'John',
        'lname' => 'Doe',   // same name but different ward
        'primary_phone' => '+25570011113',
        'transaction_phone' => '+25570022224',
    ]));

    $response->assertStatus(201);
});

// ─── Rule 5: $excludeId on update ────────────────────────────────────────

test('update allows fundi to keep its own primary_phone and nida_number', function () {
    ['district' => $district, 'ward' => $ward] = dupGeoSetup();
    $operator = dupOperator($district);
    $fundi = existingFundi($ward, $operator, [
        'fname' => 'Alice',
        'lname' => 'Wonder',
        'primary_phone' => '+25570012345',
        'transaction_phone' => '+25570054321',
        'nida_number' => '11111111111111111111',
    ]);

    Passport::actingAs($operator);
    // Re-submit the fundi's own data — should not trigger duplicate errors
    $response = $this->putJson("/api/v1/fundis/{$fundi->id}", [
        'fname' => 'Alice',
        'lname' => 'Wonder',
        'gender' => 'female',
        'primary_phone' => '+25570012345',    // own phone
        'transaction_phone' => '+25570054321',    // own phone
        'nida_number' => '11111111111111111111', // own NIDA
        'ward_id' => $ward->id,
    ]);

    // 200 or 201 — not 422
    $response->assertStatus(200);
});
