<div>
    @if (\App\Support\RolePreview::isAvailable())
        <div class="fixed bottom-6 right-6 z-40">
            <flux:dropdown position="top" align="end">
                <flux:button
                    variant="{{ $previewRole ? 'primary' : 'filled' }}"
                    icon="eye"
                    icon:trailing="chevron-up"
                    class="shadow-lg">
                    {{ $previewRole ? 'Viewing as '.$previewRole : 'View as role' }}
                </flux:button>

                <flux:menu class="w-72">
                    <div class="px-3 py-2">
                        <flux:text size="sm" class="font-medium">Show alerts for</flux:text>
                        <flux:text size="sm" class="mt-0.5 text-zinc-500">
                            You hold every permission, so this is the only way to see what each job is actually being asked to do.
                        </flux:text>
                    </div>

                    <flux:menu.separator />

                    @foreach ($this->roles as $role)
                        <flux:menu.item
                            wire:key="pr-{{ $role }}"
                            wire:click="$set('previewRole', @js($role))"
                            :icon="$previewRole === $role ? 'check' : null">
                            <div class="flex w-full items-center justify-between gap-3">
                                <span>{{ $role }}</span>
                                @if (($this->counts[$role] ?? 0) > 0)
                                    <flux:badge size="sm" color="amber">{{ $this->counts[$role] }}</flux:badge>
                                @endif
                            </div>
                        </flux:menu.item>
                    @endforeach

                    @if ($previewRole)
                        <flux:menu.separator />
                        <flux:menu.item icon="x-mark" wire:click="clear">
                            Back to my own view
                        </flux:menu.item>
                    @endif
                </flux:menu>
            </flux:dropdown>
        </div>
    @endif
</div>
