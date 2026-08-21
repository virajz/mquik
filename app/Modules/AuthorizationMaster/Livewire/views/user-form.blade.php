<div>
    <flux:modal name="authorization-master-user-form" :dismissible="false" class="md:w-lg">
        @if ($createdNotice)
            {{-- No password here on purpose: the user sets their own via a code
                 sent to their phone. Nobody, including an admin, sees a password. --}}
            <div class="space-y-5">
                <div>
                    <flux:heading size="lg">User created</flux:heading>
                    <flux:subheading>
                        A verification code has been sent to +91 {{ $createdFor }}. They use it to set their own
                        password — nothing needs to be shared by you.
                    </flux:subheading>
                </div>

                @if ($mockCode)
                    <flux:callout variant="warning" icon="beaker" heading="SMS is mocked in this environment">
                        <div class="mt-2 space-y-2">
                            <flux:text size="sm">The code that would have been texted:</flux:text>
                            <flux:input value="{{ $mockCode }}" readonly copyable
                                class:input="font-mono text-lg tracking-[0.3em] text-center" />
                            <flux:text size="sm" class="text-zinc-500">
                                It is also in the application log. Once a real SMS gateway is connected this box disappears.
                            </flux:text>
                        </div>
                    </flux:callout>
                @endif

                <div class="flex justify-end">
                    <flux:button variant="primary" icon="check" wire:click="dismissNotice">Done</flux:button>
                </div>
            </div>
        @else
            <form wire:submit="save" class="space-y-5">
                <div>
                    <flux:heading size="lg">New user</flux:heading>
                    <flux:subheading>
                        They set their own password using a code sent to their phone. You never handle a password.
                    </flux:subheading>
                </div>

                <flux:separator variant="subtle" />

                <flux:input wire:model="name" label="Name" placeholder="Full name" required autofocus />

                <flux:input wire:model="email" type="email" label="Email" placeholder="name@workshop.com"
                    icon="envelope" required />

                <flux:field>
                    <flux:label>Phone</flux:label>
                    <flux:input.group>
                        <flux:input.group.prefix>+91</flux:input.group.prefix>
                        <flux:input wire:model="phone" mask="99999 99999" inputmode="numeric" placeholder="98765 43210" />
                    </flux:input.group>
                    <flux:description>The verification code is sent here.</flux:description>
                    <flux:error name="phone" />
                </flux:field>

                <flux:field>
                    <flux:label>Roles</flux:label>
                    <flux:checkbox.group wire:model="selectedRoles" class="grid grid-cols-2 gap-2">
                        @foreach ($roles as $role)
                            <flux:checkbox :value="$role->id" :label="$role->name" wire:key="role-{{ $role->id }}" />
                        @endforeach
                    </flux:checkbox.group>
                    <flux:error name="selectedRoles" />
                </flux:field>

                <flux:separator variant="subtle" />

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" icon="check">Create user</flux:button>
                </div>
            </form>
        @endif
    </flux:modal>
</div>
