<?php

use App\Livewire\Auth\ResetPasswordWithOtp;
use App\Models\User;
use App\Models\UserOtp;
use App\Modules\AuthorizationMaster\Livewire\UserForm;
use App\Modules\AuthorizationMaster\Livewire\Users;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    RateLimiter::clear('otp-send:9876500999');
});

it('loads the reset page', function () {
    $this->get(route('password.otp'))->assertOk()->assertSee('Reset password');
});

it('sets a new password with a valid code', function () {
    $user = User::factory()->create(['phone' => '9876500999', 'password' => Hash::make('old-password')]);

    $component = Livewire::test(ResetPasswordWithOtp::class)
        ->set('phone', '9876500999')
        ->call('sendCode');

    $code = $component->get('mockCode');

    $component->set('code', $code)
        ->set('password', 'a-brand-new-one')
        ->set('password_confirmation', 'a-brand-new-one')
        ->call('verify')
        ->assertHasNoErrors();

    expect(Hash::check('a-brand-new-one', $user->fresh()->password))->toBeTrue()
        ->and($user->fresh()->must_reset_password)->toBeFalse()
        ->and($user->fresh()->phone_verified_at)->not->toBeNull();
});

it('rejects a wrong code', function () {
    User::factory()->create(['phone' => '9876500999']);

    Livewire::test(ResetPasswordWithOtp::class)
        ->set('phone', '9876500999')
        ->call('sendCode')
        ->set('code', '000000')
        ->set('password', 'a-brand-new-one')
        ->set('password_confirmation', 'a-brand-new-one')
        ->call('verify')
        ->assertHasErrors(['code']);
});

it('burns the code so it cannot be reused', function () {
    $user = User::factory()->create(['phone' => '9876500999']);

    $component = Livewire::test(ResetPasswordWithOtp::class)->set('phone', '9876500999')->call('sendCode');
    $code = $component->get('mockCode');

    $component->set('code', $code)->set('password', 'first-password')->set('password_confirmation', 'first-password')
        ->call('verify')->assertHasNoErrors();

    // Same code again — must not work a second time.
    Livewire::test(ResetPasswordWithOtp::class)
        ->set('phone', '9876500999')
        ->set('step', 'code')
        ->set('code', $code)
        ->set('password', 'second-password')
        ->set('password_confirmation', 'second-password')
        ->call('verify')
        ->assertHasErrors(['code']);

    expect(Hash::check('first-password', $user->fresh()->password))->toBeTrue();
});

it('stores the code hashed, never in plain text', function () {
    $user = User::factory()->create(['phone' => '9876500999']);

    $code = Livewire::test(ResetPasswordWithOtp::class)->set('phone', '9876500999')->call('sendCode')->get('mockCode');

    expect(UserOtp::where('user_id', $user->id)->value('code_hash'))->not->toBe($code);
});

it('does not reveal whether a number is registered', function () {
    // An unknown number reaches the same screen as a known one.
    Livewire::test(ResetPasswordWithOtp::class)
        ->set('phone', '9876500999')
        ->call('sendCode')
        ->assertHasNoErrors()
        ->assertSet('step', 'code');

    expect(UserOtp::count())->toBe(0);
});

it('throttles repeated requests for the same number', function () {
    User::factory()->create(['phone' => '9876500999']);

    foreach (range(1, 3) as $ignored) {
        Livewire::test(ResetPasswordWithOtp::class)->set('phone', '9876500999')->call('sendCode');
    }

    Livewire::test(ResetPasswordWithOtp::class)
        ->set('phone', '9876500999')
        ->call('sendCode')
        ->assertHasErrors(['phone']);
});

it('lets an admin send a reset code without ever seeing a password', function () {
    $this->actingAs(adminUser());
    $user = User::factory()->create(['phone' => '9876500777', 'name' => 'RESET TARGET']);
    $before = $user->password;

    $component = Livewire::test(Users::class)
        ->call('sendPasswordReset', $user->id);

    expect($component->get('mockCode'))->toHaveLength(6)
        ->and($user->fresh()->must_reset_password)->toBeTrue()
        // The admin action must not touch the password itself.
        ->and($user->fresh()->password)->toBe($before)
        ->and(UserOtp::where('user_id', $user->id)->count())->toBe(1);
});

it('warns instead of sending when the user has no phone', function () {
    $this->actingAs(adminUser());
    $user = User::factory()->create(['phone' => null]);

    Livewire::test(Users::class)
        ->call('sendPasswordReset', $user->id)
        ->assertSet('mockCode', null);

    expect(UserOtp::where('user_id', $user->id)->count())->toBe(0);
});

it('creates a user with a phone and never exposes a password', function () {
    $this->actingAs(adminUser());

    $component = Livewire::test(UserForm::class)
        ->set('userType', UserForm::TYPE_MANUAL)
        ->set('name', 'New Person')
        ->set('email', 'new.person@mquik.test')
        ->set('phone', '9876500888')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'new.person@mquik.test')->firstOrFail();

    expect($user->phone)->toBe('9876500888')
        ->and($user->must_reset_password)->toBeTrue()
        ->and($component->html())->not->toContain('temporary password');
});

it('requires a unique phone number', function () {
    $this->actingAs(adminUser());
    User::factory()->create(['phone' => '9876500999']);

    Livewire::test(UserForm::class)
        ->set('userType', UserForm::TYPE_MANUAL)
        ->set('name', 'Clashing')
        ->set('email', 'clash@mquik.test')
        ->set('phone', '9876500999')
        ->call('save')
        ->assertHasErrors(['phone']);
});
