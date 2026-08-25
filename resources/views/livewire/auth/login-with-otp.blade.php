<div class="space-y-6">
    <div>
        <flux:heading size="lg">Sign in with a code</flux:heading>
        <flux:subheading>No password — we send a one-time code to your mobile or email.</flux:subheading>
    </div>

    @if ($step === 'identifier')
        <form wire:submit="sendCode" novalidate class="space-y-4">
            <flux:input wire:model="identifier" label="Mobile or Email"
                placeholder="98765 43210 or name@workshop.com" autofocus required />

            <flux:checkbox wire:model="remember" label="Keep me signed in" />

            <flux:button type="submit" variant="primary" class="w-full">Send code</flux:button>
        </form>
    @else
        <form wire:submit="verify" novalidate class="space-y-4">
            <flux:text size="sm" class="text-zinc-500">
                If <span class="font-medium">{{ $identifier }}</span> has an account, a code is on its way.
            </flux:text>

            @if ($mockCode)
                <flux:callout variant="secondary" inline>
                    <flux:callout.text>Mock delivery — your code is <span class="font-mono font-semibold">{{ $mockCode }}</span></flux:callout.text>
                </flux:callout>
            @endif

            <flux:input wire:model="code" label="Code" placeholder="123456" maxlength="6"
                inputmode="numeric" class:input="font-mono tracking-widest text-center" autofocus required />

            <flux:button type="submit" variant="primary" class="w-full">Sign in</flux:button>
            <flux:button variant="ghost" class="w-full" wire:click="$set('step', 'identifier')">Use a different number or email</flux:button>
        </form>
    @endif

    <flux:separator variant="subtle" />

    <flux:text size="sm" class="text-center text-zinc-500">
        Have a password? <flux:link :href="route('login')">Sign in with it instead</flux:link>
    </flux:text>
</div>
