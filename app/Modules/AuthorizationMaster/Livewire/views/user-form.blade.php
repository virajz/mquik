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

                {{-- Who this login is for. Employees and contractors already exist
                     in a master, so pick them rather than retyping their details. --}}
                <flux:select wire:model.live="userType" variant="listbox" label="User type" required>
                    @foreach (\App\Modules\AuthorizationMaster\Livewire\UserForm::userTypes() as $key => $label)
                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                @if ($userType === 'employee')
                    <flux:field>
                        <flux:label>Employee</flux:label>
                        <div class="flex items-stretch gap-2">
                            <div class="min-w-0 flex-1">
                                <flux:select wire:model.live="employeeId" variant="listbox" searchable clearable
                                    placeholder="Pick an employee…">
                                    @foreach ($this->employees as $e)
                                        <flux:select.option :value="$e->id" wire:key="emp-{{ $e->id }}">
                                            {{ $e->name }}@if ($e->phone) · +91 {{ $e->phone }} @endif
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                            @can('employee_master.create')
                                <flux:tooltip content="Employee not on file? Add them">
                                    <flux:button type="button" icon="plus" variant="ghost"
                                        wire:click="$dispatch('authorization-master:quick-add-person', { type: 'employee' })" />
                                </flux:tooltip>
                            @endcan
                        </div>
                        <flux:description>Only employees without a login are listed.</flux:description>
                        <flux:error name="employeeId" />
                    </flux:field>
                @elseif ($userType === 'contractor')
                    <flux:field>
                        <flux:label>Service Contractor</flux:label>
                        <div class="flex items-stretch gap-2">
                            <div class="min-w-0 flex-1">
                                <flux:select wire:model.live="vendorId" variant="listbox" searchable clearable
                                    placeholder="Pick a contractor…">
                                    @foreach ($this->contractors as $v)
                                        <flux:select.option :value="$v->id" wire:key="ven-{{ $v->id }}">
                                            {{ $v->name }}@if ($v->phone) · +91 {{ $v->phone }} @endif
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                            @can('vendor_master.create')
                                <flux:tooltip content="Contractor not on file? Add them">
                                    <flux:button type="button" icon="plus" variant="ghost"
                                        wire:click="$dispatch('authorization-master:quick-add-person', { type: 'contractor' })" />
                                </flux:tooltip>
                            @endcan
                        </div>
                        <flux:description>Vendors filed as “Service Contractor”, without a login.</flux:description>
                        <flux:error name="vendorId" />
                    </flux:field>
                @endif

                <flux:input wire:model="name" label="Name" placeholder="Full name" required autofocus
                    :readonly="$userType !== 'manual'"
                    :description="$userType !== 'manual' ? 'From the master record.' : null" />

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
