<?php
?>
<div>
    <flux:modal name="attendance-form" class="md:w-lg">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Attendance' : 'New Attendance Punch' }}
                </flux:heading>
                <flux:subheading>
                    {{ $editingId ? 'Update the punch details below.' : 'Record an employee punch — in or out.' }}
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <flux:select wire:model="employee_id" variant="listbox" searchable label="Employee" placeholder="Pick an employee…" required autofocus>
                    @foreach ($this->employees as $e)
                        <flux:select.option :value="$e->id" wire:key="emp-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:date-picker wire:model="punched_date" label="Punch Date" with-today selectable-header fixed-weeks type="input" />
                    <flux:time-picker wire:model="punched_time" label="Punch Time" type="input" />
                </div>

                <flux:select wire:model="type" variant="listbox" label="Punch Type" required>
                    @foreach (\App\Modules\Attendance\Models\Attendance::types() as $key => $label)
                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="space-y-2">
                    <flux:text size="sm" class="font-medium">Selfie</flux:text>
                    @if ($existing_selfie_path && ! $clearSelfie)
                        <div class="flex items-center gap-3">
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($existing_selfie_path) }}" alt="Existing selfie" class="size-16 rounded object-cover border border-zinc-200 dark:border-zinc-800" />
                            <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="markClearSelfie">Remove</flux:button>
                        </div>
                    @endif

                    <flux:file-upload wire:model="selfie" accept="image/*">
                        <flux:file-upload.dropzone>
                            <flux:icon.camera class="size-6 text-zinc-400" />
                            <span class="text-sm font-medium">{{ $existing_selfie_path && ! $clearSelfie ? 'Replace selfie' : 'Upload selfie' }}</span>
                            <flux:text size="xs" class="text-zinc-500">JPG / PNG · up to 4 MB</flux:text>
                        </flux:file-upload.dropzone>
                    </flux:file-upload>

                    @if ($selfie)
                        <div class="flex items-center gap-3">
                            <img src="{{ $selfie->temporaryUrl() }}" alt="" class="size-16 rounded object-cover border border-zinc-200 dark:border-zinc-800" />
                            <flux:text size="xs" class="text-zinc-500">Saved on submit.</flux:text>
                        </div>
                    @endif
                    <flux:error name="selfie" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input wire:model="latitude" label="Latitude" placeholder="Optional" class:input="font-mono text-xs" />
                    <flux:input wire:model="longitude" label="Longitude" placeholder="Optional" class:input="font-mono text-xs" />
                </div>

                <flux:textarea wire:model="notes" label="Notes" placeholder="Optional notes about the punch" rows="2" />
            </div>

            <flux:separator variant="subtle" />

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">
                    {{ $editingId ? 'Save changes' : 'Record Punch' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
