<?php

use App\Livewire\Auth\LoginWithOtp;
use App\Models\User;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| OTP sign-in — guest flows, no admin session
|--------------------------------------------------------------------------
*/

it('signs a user in with a code sent to their phone', function () {
    $user = User::factory()->create(['phone' => '9812345678', 'is_active' => true]);

    $component = Livewire::test(LoginWithOtp::class)
        ->set('identifier', '98123 45678')
        ->call('sendCode');

    $code = $component->get('mockCode');
    expect($code)->toHaveLength(6);

    $component->set('code', '000000')->call('verify')->assertHasErrors(['code']);
    expect(auth()->check())->toBeFalse();

    $component->set('code', $code)->call('verify')->assertHasNoErrors();
    expect(auth()->id())->toBe($user->id);
});

it('signs a user in with a code sent to their email', function () {
    $user = User::factory()->create(['email' => 'otp-login@example.com', 'is_active' => true]);

    $component = Livewire::test(LoginWithOtp::class)
        ->set('identifier', 'OTP-Login@Example.com')
        ->call('sendCode');

    $component->set('code', $component->get('mockCode'))->call('verify')->assertHasNoErrors();
    expect(auth()->id())->toBe($user->id);
});

it('shows the same code-sent screen for an unknown identifier', function () {
    $component = Livewire::test(LoginWithOtp::class)
        ->set('identifier', 'nobody@nowhere.test')
        ->call('sendCode');

    expect($component->get('step'))->toBe('code')
        ->and($component->get('mockCode'))->toBeNull();
});

it('refuses to sign in an inactive user even with the right code', function () {
    $user = User::factory()->create(['phone' => '9800000001', 'is_active' => true]);

    $component = Livewire::test(LoginWithOtp::class)
        ->set('identifier', '9800000001')
        ->call('sendCode');

    $user->forceFill(['is_active' => false])->save();

    $component->set('code', $component->get('mockCode'))->call('verify')->assertHasErrors(['code']);
    expect(auth()->check())->toBeFalse();
});
