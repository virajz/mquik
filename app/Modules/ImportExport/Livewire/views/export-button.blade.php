<div>
    {{-- Progress modal — opens when an export is dispatched.
         Updates flow exclusively via Reverb broadcasts (see #[On('echo-private:...')] in ExportButton). --}}
    <flux:modal :name="'export-progress-' . $module" class="md:w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">
                    @if ($status === 'completed')
                        Export ready
                    @elseif ($status === 'failed')
                        Export failed
                    @else
                        Generating export…
                    @endif
                </flux:heading>
                <flux:subheading>
                    @if ($status === 'completed')
                        Your CSV is ready to download. Link expires in 24 hours.
                    @elseif ($status === 'failed')
                        Something went wrong. {{ $errorMessage }}
                    @else
                        Hang tight — we're streaming your records into a CSV. You can close this and come back; we'll notify you when it's done.
                    @endif
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            @php
                $barColor = match ($status) {
                    'completed' => 'bg-emerald-500',
                    'failed' => 'bg-red-500',
                    default => 'bg-mq-orange-500',
                };
            @endphp
            <div class="space-y-3">
                <div class="flex items-baseline justify-between text-sm">
                    <span class="text-zinc-500">
                        @if ($status === 'completed') Done
                        @elseif ($status === 'failed') Failed
                        @else Processing
                        @endif
                    </span>
                    <span class="font-mono font-medium">{{ $percent }}%</span>
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                    <div class="h-full {{ $barColor }} transition-all duration-300"
                        style="width: {{ $percent }}%"></div>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">
                        {{ $status === 'completed' ? 'Close' : 'Run in background' }}
                    </flux:button>
                </flux:modal.close>

                @if ($status === 'completed' && $downloadUrl)
                    <flux:button variant="primary" icon="arrow-down-tray" :href="$downloadUrl">
                        Download CSV
                    </flux:button>
                @endif
            </div>
        </div>
    </flux:modal>
</div>
