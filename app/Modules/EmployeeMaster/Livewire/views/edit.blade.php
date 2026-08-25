<div>
    <form wire:submit="save" novalidate class="max-w-3xl">
        <div class="mb-8">
            <flux:link :href="route('employee-master.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Employees
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">
                {{ $editingId ? ($name ?: 'Employee '.$employee_code) : 'New Employee' }}
            </flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Workshop staff — advisors, technicians, cashiers, accountants, drivers, security guards.</flux:text>
        </div>

        <flux:separator />

        {{-- IDENTITY --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Identity</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Code, name and basic details.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:input wire:model="employee_code" label="Employee Code" placeholder="EMP-00001" required class:input="font-mono uppercase tracking-wide" />
                    <div class="md:col-span-2">
                        <flux:input wire:model="name" label="Name" placeholder="Employee name" required autofocus />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="gender" label="Gender" variant="listbox" placeholder="Optional">
                        <flux:select.option value="">— Skip —</flux:select.option>
                        <flux:select.option value="male">Male</flux:select.option>
                        <flux:select.option value="female">Female</flux:select.option>
                        <flux:select.option value="other">Other</flux:select.option>
                    </flux:select>
                    <flux:date-picker locale="en-IN" wire:model="date_of_birth" label="Date of Birth" placeholder="Select date" with-today selectable-header fixed-weeks type="input" />
                    <flux:input wire:model="city" label="City" placeholder="e.g. AHMEDABAD" />
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- CONTACT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Contact</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">How to reach them.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:field>
                        <flux:label>Phone</flux:label>
                        <flux:input.group>
                            <flux:input.group.prefix>+91</flux:input.group.prefix>
                            <flux:input wire:model="phone" mask="99999 99999" placeholder="98765 43210" inputmode="numeric" required />
                        </flux:input.group>
                        <flux:error name="phone" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Alternate Phone</flux:label>
                        <flux:input.group>
                            <flux:input.group.prefix>+91</flux:input.group.prefix>
                            <flux:input wire:model="alternate_phone" mask="99999 99999" placeholder="98765 43210" inputmode="numeric" />
                        </flux:input.group>
                    </flux:field>
                </div>

                <flux:input wire:model="email" type="email" label="Email" placeholder="employee@workshop.com" icon="envelope" />
                <flux:textarea wire:model="address" label="Address" rows="2" />
                <flux:input wire:model="pincode" label="Pincode" mask="999999" inputmode="numeric" maxlength="6" class="md:max-w-xs" />
            </div>
        </section>

        <flux:separator />

        {{-- EMPLOYMENT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Employment</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Role, grade, CTC and dates.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="designation_id" label="Designation" variant="combobox" required>
                        <x-slot name="input">
                            <flux:select.input wire:model="designationSearch" placeholder="Pick or type to add…" />
                        </x-slot>
                        @foreach ($designations as $d)
                            <flux:select.option :value="$d->id" wire:key="dg-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                        @endforeach
                        @can('designation_master.create')
                            <flux:select.option.create wire:click="createDesignation" min-length="2">
                                Create "<span wire:text="designationSearch"></span>"
                            </flux:select.option.create>
                        @endcan
                    </flux:select>
                    <flux:select wire:model="department_id" label="Department" variant="combobox" required>
                        <x-slot name="input">
                            <flux:select.input wire:model="departmentSearch" placeholder="Pick or type to add…" />
                        </x-slot>
                        @foreach ($departments as $d)
                            <flux:select.option :value="$d->id" wire:key="dp-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                        @endforeach
                        @can('department_master.create')
                            <flux:select.option.create wire:click="createDepartment" min-length="2">
                                Create "<span wire:text="departmentSearch"></span>"
                            </flux:select.option.create>
                        @endcan
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="employee_category_id" label="Category" variant="listbox" clearable placeholder="Permanent / Probation…">
                        @foreach ($categories as $c)
                            <flux:select.option :value="$c->id" wire:key="cat-{{ $c->id }}">{{ $c->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="employee_grade_id" label="Grade" variant="listbox" clearable placeholder="Grade A / B / C…">
                        @foreach ($grades as $g)
                            <flux:select.option :value="$g->id" wire:key="grd-{{ $g->id }}">{{ $g->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="ctc" type="number" step="0.01" min="0" label="Annual CTC" placeholder="Cost to company" class:input="text-right font-mono" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:date-picker locale="en-IN" wire:model="joining_date" label="Joining Date" placeholder="Select date" with-today selectable-header fixed-weeks type="input" />
                    <flux:date-picker locale="en-IN" wire:model="exit_date" label="Exit Date" placeholder="Still employed" with-today selectable-header fixed-weeks type="input" />
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- KYC & BANKING --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">KYC & Banking</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Identity and salary account.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input wire:model="aadhar" label="Aadhar" mask="9999 9999 9999" placeholder="0000 0000 0000" class:input="font-mono uppercase tracking-wide" inputmode="numeric" />
                    </div>
                    <flux:input wire:model="pan" label="PAN" placeholder="ABCDE1234F" maxlength="10" class:input="font-mono uppercase tracking-wide" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:input wire:model="bank_name" label="Bank Name" placeholder="e.g. HDFC BANK" />
                    <flux:input wire:model="bank_branch" label="Branch" placeholder="e.g. SATELLITE" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:input wire:model="ifsc" label="IFSC" placeholder="HDFC0000001" maxlength="11" class:input="font-mono uppercase tracking-wide" />
                    <flux:input wire:model="account_no" label="Account No" placeholder="Account number" class:input="font-mono tracking-wide" />
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- STATUS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Notes & Status</flux:heading>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:textarea wire:model="notes" label="Internal Notes" rows="2" />
                <flux:switch wire:model="is_active" label="Active" description="Inactive employees won't appear in advisor / technician dropdowns." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('employee-master.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create employee' }}</flux:button>
        </div>
    </form>
</div>
