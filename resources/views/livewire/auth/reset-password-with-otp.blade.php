<div class="flex flex-col gap-6">
        @if ($step === 'phone')
            <x-auth-header :title="__('Reset password')"
                :description="__('Enter your phone number and we will text you a code')" />

            <form wire:submit="sendCode" novalidate class="flex flex-col gap-6">
                <flux:field>
                    <flux:label>{{ __('Phone') }}</flux:label>
                    <flux:input.group>
                        <flux:input.group.prefix>+91</flux:input.group.prefix>
                        <flux:input wire:model="phone" mask="99999 99999" inputmode="numeric"
                            placeholder="98765 43210" autofocus />
                    </flux:input.group>
                    <flux:error name="phone" />
                </flux:field>

                <flux:button variant="primary" type="submit" class="w-full">{{ __('Send code') }}</flux:button>
            </form>

            <flux:text class="text-center">
                {{ __('Remembered it?') }}
                <flux:link :href="route('login')" wire:navigate>{{ __('Sign in') }}</flux:link>
            </flux:text>

        @elseif ($step === 'code')
            {{-- Worded so it says the same thing whether or not the number is
                 registered — otherwise this page tells you who has an account. --}}
            <x-auth-header :title="__('Enter the code')"
                :description="__('If that number is registered, a 6-digit code is on its way. It expires in 10 minutes.')" />

            @if ($mockCode)
                <flux:callout variant="warning" icon="beaker" heading="SMS is mocked">
                    <flux:text size="sm" class="mt-1">Your code is <strong class="font-mono tracking-widest">{{ $mockCode }}</strong>.</flux:text>
                </flux:callout>
            @endif

            <form wire:submit="verify" novalidate class="flex flex-col gap-6">
                <flux:input wire:model="code" :label="__('6-digit code')" placeholder="000000"
                    maxlength="6" inputmode="numeric" class:input="text-center font-mono text-lg tracking-[0.4em]" autofocus />

                <flux:input wire:model="password" type="password" :label="__('New password')"
                    placeholder="At least 8 characters" viewable />

                <flux:input wire:model="password_confirmation" type="password" :label="__('Confirm password')"
                    placeholder="Repeat it" viewable />

                <flux:button variant="primary" type="submit" class="w-full">{{ __('Set password') }}</flux:button>
            </form>

            <flux:text class="text-center">
                <flux:link wire:click="startOver" class="cursor-pointer">{{ __('Use a different number') }}</flux:link>
            </flux:text>

        @else
            <x-auth-header :title="__('Password set')" :description="__('You can sign in with your new password.')" />

            <flux:button variant="primary" :href="route('login')" wire:navigate class="w-full">
                {{ __('Go to sign in') }}
            </flux:button>
        @endif
    </div>
