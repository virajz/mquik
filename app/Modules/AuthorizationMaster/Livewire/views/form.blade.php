<div>
    <flux:modal name="authorization-master-form" class="md:w-3xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Role' : 'New Role' }}
                </flux:heading>
                <flux:subheading>
                    {{ $editingId
                        ? 'Update the role name and permission grants below.'
                        : 'Create a new role and pick the actions members of that role can perform.' }}
                </flux:subheading>
            </div>

            @if ($isSuperAdmin)
                <flux:callout variant="warning" icon="shield-check">
                    <flux:callout.heading>System role</flux:callout.heading>
                    <flux:callout.text>
                        This is a system role and always has every permission. You can rename it but
                        not change its access.
                    </flux:callout.text>
                </flux:callout>
            @endif

            <flux:separator variant="subtle" />

            <flux:input
                wire:model="name"
                label="Role Name"
                placeholder="e.g. Workshop Manager"
                required
                autofocus
            />

            <flux:separator variant="subtle" />

            <div>
                <flux:heading size="sm">Permissions</flux:heading>
                <flux:subheading>Tick what members of this role can do.</flux:subheading>
            </div>

            <div class="space-y-5 max-h-[55vh] overflow-y-auto pr-1">
                @forelse ($grouped as $group => $modules)
                    @php
                        $groupSlugs = collect($modules)
                            ->flatMap(fn ($actions) => collect($actions)->pluck('name'))
                            ->all();
                        $groupCount = count($groupSlugs);
                        $checkedInGroup = count(array_intersect($groupSlugs, $permissions));
                        $allChecked = $groupCount > 0 && $checkedInGroup === $groupCount;
                    @endphp

                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                            <div>
                                <div class="font-medium text-sm">{{ $group }}</div>
                                <div class="text-xs text-zinc-500">{{ $checkedInGroup }} / {{ $groupCount }} permissions selected</div>
                            </div>

                            <flux:checkbox
                                label="Select all"
                                :checked="$allChecked"
                                :disabled="$isSuperAdmin"
                                wire:click="toggleGroup('{{ $group }}', {{ $allChecked ? 'false' : 'true' }})"
                            />
                        </div>

                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($modules as $moduleLabel => $actions)
                                <div class="px-4 py-3">
                                    <div class="text-xs font-medium text-zinc-500 mb-2">{{ $moduleLabel }}</div>
                                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2">
                                        @foreach ($actions as $action => $permission)
                                            <flux:checkbox
                                                wire:model="permissions"
                                                value="{{ $permission->name }}"
                                                :label="ucfirst($action)"
                                                :disabled="$isSuperAdmin"
                                            />
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <flux:callout icon="exclamation-triangle">
                        <flux:callout.text>No permissions registered yet. Run <code>php artisan auth:sync-permissions</code>.</flux:callout.text>
                    </flux:callout>
                @endforelse
            </div>

            <flux:separator variant="subtle" />

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">
                    {{ $editingId ? 'Save changes' : 'Create role' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
