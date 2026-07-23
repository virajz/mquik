<div>
    <flux:modal name="employee-master-form" :dismissible="false" class="md:w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit Employee' : 'New Employee' }}</flux:heading>
                <flux:subheading>Workshop staff — advisors, technicians, cashiers, accountants, drivers, security guards.</flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                {{-- IDENTITY --}}
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
                    <flux:date-picker wire:model="date_of_birth" label="Date of Birth" placeholder="Select date" with-today selectable-header fixed-weeks type="input" />
                    <flux:input wire:model="city" label="City" placeholder="e.g. AHMEDABAD" />
                </div>

                {{-- CONTACT --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:field>
                        <flux:label>Phone <span class="text-red-500">*</span></flux:label>
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

                <flux:input wire:model="pincode" label="Pincode" mask="999999" inputmode="numeric" maxlength="6" />

                <flux:separator variant="subtle" />

                {{-- EMPLOYMENT --}}
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
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
                    <flux:date-picker wire:model="joining_date" label="Joining Date" placeholder="Select date" with-today selectable-header fixed-weeks type="input" />
                    <flux:date-picker wire:model="exit_date" label="Exit Date" placeholder="Still employed" with-today selectable-header fixed-weeks type="input" />
                </div>

                <flux:separator variant="subtle" />

                {{-- KYC --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input wire:model="aadhar" label="Aadhar" mask="9999 9999 9999" placeholder="0000 0000 0000" class:input="font-mono uppercase tracking-wide" inputmode="numeric" />
                    </div>
                    <flux:input wire:model="pan" label="PAN" placeholder="ABCDE1234F" maxlength="10" class:input="font-mono uppercase tracking-wide" />
                </div>

                <flux:separator variant="subtle" />

                {{-- BANKING --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:input wire:model="bank_name" label="Bank Name" placeholder="e.g. HDFC BANK" />
                    <flux:input wire:model="bank_branch" label="Branch" placeholder="e.g. SATELLITE" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:input wire:model="ifsc" label="IFSC" placeholder="HDFC0000001" maxlength="11" class:input="font-mono uppercase tracking-wide" />
                    <flux:input wire:model="account_no" label="Account No" placeholder="Account number" class:input="font-mono tracking-wide" />
                </div>

                <flux:textarea wire:model="notes" label="Internal Notes" rows="2" />

                <flux:separator variant="subtle" />

                <flux:switch wire:model="is_active" label="Active" description="Inactive employees won't appear in advisor / technician dropdowns." />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
