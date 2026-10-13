<?php

use App\Models\District;
use App\Models\Fundi;
use App\Models\Region;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('public'));

test('root and admin can view any fundi', function () {
    ['ward' => $ward] = policyGeo();
    $creator = User::factory()->create(['role_id' => 3, 'status' => 'active']);
    $fundi = policyFundi($ward, $creator, 'FND910001');

    $root = User::factory()->create(['role_id' => 1, 'status' => 'active']);
    $admin = User::factory()->create(['role_id' => 2, 'status' => 'active']);

    expect($root->can('view', $fundi))->toBeTrue()
        ->and($admin->can('view', $fundi))->toBeTrue();
});

test('operator can view a fundi only in an assigned district', function () {
    ['district' => $district, 'otherDistrict' => $otherDistrict, 'ward' => $ward, 'otherWard' => $otherWard] = policyGeo();
    $operator = User::factory()->create(['role_id' => 3, 'status' => 'active']);
    $operator->districts()->attach($district->id);

    $ownFundi = policyFundi($ward, $operator, 'FND910002');
    $otherFundi = policyFundi($otherWard, $operator, 'FND910003');

    expect($operator->can('view', $ownFundi))->toBeTrue()
        ->and($operator->can('update', $ownFundi))->toBeTrue()
        ->and($operator->can('view', $otherFundi))->toBeFalse()
        ->and($otherDistrict->id)->not->toBe($district->id);
});

function policyGeo(): array
{
    $region = Region::create(['name' => 'Policy Region '.uniqid()]);
    $district = District::create(['region_id' => $region->id, 'name' => 'Policy District '.uniqid()]);
    $otherDistrict = District::create(['region_id' => $region->id, 'name' => 'Policy Other '.uniqid()]);
    $ward = Ward::create(['district_id' => $district->id, 'name' => 'Policy Ward']);
    $otherWard = Ward::create(['district_id' => $otherDistrict->id, 'name' => 'Policy Other Ward']);

    return compact('district', 'otherDistrict', 'ward', 'otherWard');
}

function policyFundi(Ward $ward, User $creator, string $uniqueId): Fundi
{
    return Fundi::create([
        'unique_id' => $uniqueId,
        'created_by' => $creator->id,
        'fname' => 'Policy',
        'lname' => 'Fundi',
        'gender' => 'male',
        'primary_phone' => '+2557'.random_int(10000000, 99999999),
        'transaction_phone' => '+2556'.random_int(10000000, 99999999),
        'ward_id' => $ward->id,
        'ranking' => 'fundi_kuu',
        'status' => 'active',
    ]);
}
