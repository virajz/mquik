<?php

use App\Concerns\Auditable;
use App\Models\AuditLog;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('writes a created audit row with new_values populated and the current user_id', function () {
    AuditLog::query()->delete();
    $userId = auth()->id();

    $brand = SpareBrandMaster::create(['name' => 'BOSCH']);

    $log = AuditLog::query()->where('model_type', SpareBrandMaster::class)
        ->where('model_id', $brand->id)
        ->where('event', 'created')
        ->firstOrFail();

    expect($log->user_id)->toBe($userId)
        ->and($log->new_values)->toBeArray()
        ->and($log->new_values['name'] ?? null)->toBe('BOSCH')
        ->and($log->old_values)->toBeNull()
        ->and($log->model_label)->toBe('BOSCH');
});

it('writes an updated audit row with old_values and new_values for changed fields', function () {
    $brand = SpareBrandMaster::create(['name' => 'OLD NAME']);
    AuditLog::query()->delete();

    $brand->update(['name' => 'NEW NAME']);

    $log = AuditLog::query()->where('event', 'updated')->firstOrFail();

    expect($log->old_values)->toBeArray()
        ->and($log->old_values['name'] ?? null)->toBe('OLD NAME')
        ->and($log->new_values)->toBeArray()
        ->and($log->new_values['name'] ?? null)->toBe('NEW NAME');
});

it('does not write an audit row when only the timestamp changes (no meaningful diff)', function () {
    $brand = SpareBrandMaster::create(['name' => 'BOSCH']);
    AuditLog::query()->delete();

    // Touch updated_at without changing any other column.
    $brand->touch();

    expect(AuditLog::query()->where('event', 'updated')->count())->toBe(0);
});

it('writes a deleted audit row with old_values populated and new_values null', function () {
    $brand = SpareBrandMaster::create(['name' => 'TO BE DELETED']);
    $brandId = $brand->id;
    AuditLog::query()->delete();

    $brand->delete();

    $log = AuditLog::query()->where('event', 'deleted')
        ->where('model_id', $brandId)
        ->firstOrFail();

    expect($log->old_values)->toBeArray()
        ->and($log->old_values['name'] ?? null)->toBe('TO BE DELETED')
        ->and($log->new_values)->toBeNull();
});

it('snapshots model_label from the model name attribute', function () {
    AuditLog::query()->delete();

    $brand = SpareBrandMaster::create(['name' => 'CUSTOM LABEL TEST']);

    $log = AuditLog::query()->where('event', 'created')->firstOrFail();

    expect($log->model_label)->toBe('CUSTOM LABEL TEST');
});

it('redacts secret fields like password and tokens in audit values', function () {
    // Inline anonymous model isn't viable; instead, exercise the trait's sanitize via reflection.
    $sanitizer = new ReflectionMethod(Auditable::class, 'sanitizeForAudit');

    $clean = $sanitizer->invoke(null, [
        'name' => 'plain',
        'password' => 'super-secret',
        'remember_token' => 'tok',
        'two_factor_secret' => 's2',
        'two_factor_recovery_codes' => 'r2',
    ]);

    expect($clean['name'])->toBe('plain')
        ->and($clean['password'])->toBe('***')
        ->and($clean['remember_token'])->toBe('***')
        ->and($clean['two_factor_secret'])->toBe('***')
        ->and($clean['two_factor_recovery_codes'])->toBe('***');
});

it('snapshots the user_name on each audit row', function () {
    AuditLog::query()->delete();

    SpareBrandMaster::create(['name' => 'HAS USER NAME']);

    $log = AuditLog::query()->latest('id')->firstOrFail();

    expect($log->user_name)->toBe(auth()->user()->name);
});
