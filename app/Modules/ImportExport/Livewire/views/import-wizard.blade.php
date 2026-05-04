<div>
    <flux:modal :name="'import-wizard-' . $module" class="md:w-2xl">
        <div class="space-y-5">
            {{-- Step indicator (pe-10 keeps "rows" badge clear of the modal's close X) --}}
            <div class="pe-10">
                <div class="flex items-center justify-between text-xs text-zinc-500 mb-3">
                    <span>Step {{ $step }} of 4</span>
                    @if ($totalDataRows > 0)
                        <span class="font-mono">{{ number_format($totalDataRows) }} rows</span>
                    @endif
                </div>
                <div class="grid grid-cols-4 gap-1">
                    @foreach ([1 => 'Upload', 2 => 'Map columns', 3 => 'Behavior', 4 => 'Review'] as $n => $label)
                        <div>
                            <div class="h-1 rounded-full {{ $step >= $n ? 'bg-mq-orange-500' : 'bg-zinc-200 dark:bg-zinc-700' }}"></div>
                            <div class="mt-1 text-xs {{ $step === $n ? 'font-semibold text-mq-orange-600' : 'text-zinc-500' }}">
                                {{ $label }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <flux:separator variant="subtle" />

            {{-- ============== STEP 1 — Upload ============== --}}
            @if ($step === 1)
                <div>
                    <flux:heading size="lg">Upload your CSV</flux:heading>
                    <flux:subheading>
                        Drop a CSV file (max 10 MB). The first row should be column headers.
                    </flux:subheading>
                </div>

                <div>
                    <flux:file-upload wire:model="file" accept=".csv,text/csv,text/plain">
                        @if (! $file)
                            <flux:file-upload.dropzone
                                heading="Drop your CSV here or click to browse"
                                text="CSV files up to 10 MB"
                                with-progress
                            />
                        @else
                            <flux:file-item
                                icon="document-text"
                                heading="{{ $file->getClientOriginalName() }}"
                                size="{{ $file->getSize() }}"
                            >
                                <flux:file-item.remove wire:click="$set('file', null)" />
                            </flux:file-item>
                        @endif
                    </flux:file-upload>

                    @error('file')
                        <flux:text class="text-red-500 text-sm mt-2">{{ $message }}</flux:text>
                    @enderror
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button
                        type="button"
                        variant="primary"
                        icon="arrow-right"
                        wire:click="processUpload"
                        :disabled="! $file"
                    >
                        Next
                    </flux:button>
                </div>
            @endif

            {{-- ============== STEP 2 — Map columns ============== --}}
            @if ($step === 2)
                <div>
                    <flux:heading size="lg">Map your columns</flux:heading>
                    <flux:subheading>
                        We've auto-suggested matches based on your CSV headers. Adjust as needed.
                    </flux:subheading>
                </div>

                <div class="space-y-3 max-h-96 overflow-y-auto -mx-1 px-1">
                    @foreach ($targetColumns as $targetCol => $meta)
                        <div class="grid grid-cols-2 gap-3 items-center">
                            <div>
                                <div class="font-medium text-sm">
                                    {{ $meta['label'] ?? $targetCol }}
                                    @if ($meta['required'] ?? false)
                                        <span class="text-red-500">*</span>
                                    @endif
                                </div>
                                @if (! empty($meta['help']))
                                    <div class="text-xs text-zinc-500 mt-0.5">{{ $meta['help'] }}</div>
                                @endif
                            </div>
                            <flux:select
                                wire:model="mapping.{{ $targetCol }}"
                                variant="listbox"
                                placeholder="— Skip —"
                            >
                                <flux:select.option value="">— Skip —</flux:select.option>
                                @foreach ($csvHeaders as $header)
                                    <flux:select.option :value="$header">{{ $header }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    @endforeach
                </div>

                @if (! empty($previewRows))
                    <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-3">
                        <div class="text-xs font-medium text-zinc-500 mb-2 uppercase tracking-wide">Preview (first {{ count($previewRows) }} rows)</div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr>
                                        @foreach ($csvHeaders as $h)
                                            <th class="text-left p-1 font-medium text-zinc-700 dark:text-zinc-300 whitespace-nowrap">{{ $h }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($previewRows as $row)
                                        <tr class="border-t border-zinc-200 dark:border-zinc-700">
                                            @foreach ($row as $cell)
                                                <td class="p-1 text-zinc-600 dark:text-zinc-400 font-mono">{{ $cell }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <div class="flex justify-between gap-2 pt-2">
                    <flux:button type="button" variant="ghost" icon="arrow-left" wire:click="back">
                        Back
                    </flux:button>
                    <flux:button type="button" variant="primary" icon="arrow-right" wire:click="confirmMapping">
                        Next
                    </flux:button>
                </div>
            @endif

            {{-- ============== STEP 3 — Behavior ============== --}}
            @if ($step === 3)
                <div>
                    <flux:heading size="lg">How should we handle conflicts?</flux:heading>
                    <flux:subheading>
                        Configure what happens when rows match existing records or fail validation.
                    </flux:subheading>
                </div>

                <div class="space-y-5">
                    <flux:radio.group wire:model="duplicateBehavior" label="When a row matches an existing record">
                        <flux:radio value="upsert" label="Update if exists, create if not (recommended)" />
                        <flux:radio value="create_only" label="Skip — only create new records" />
                        <flux:radio value="skip" label="Silently skip duplicates" />
                        <flux:radio value="error" label="Halt the import on the first duplicate" />
                    </flux:radio.group>

                    <flux:radio.group wire:model="errorBehavior" label="When a row fails validation">
                        <flux:radio value="skip" label="Skip the row and log it (recommended)" />
                        <flux:radio value="halt" label="Halt the entire import" />
                    </flux:radio.group>
                </div>

                <div class="flex justify-between gap-2 pt-2">
                    <flux:button type="button" variant="ghost" icon="arrow-left" wire:click="back">
                        Back
                    </flux:button>
                    <flux:button type="button" variant="primary" icon="magnifying-glass" wire:click="confirmBehavior">
                        Generate preview
                    </flux:button>
                </div>
            @endif

            {{-- ============== STEP 4 — Processing / Results ============== --}}
            @if ($step === 4)
                <div>
                    <flux:heading size="lg">
                        @if ($processStatus === 'processing')
                            {{ $isDryRun ? 'Generating preview…' : 'Importing records…' }}
                        @elseif ($processStatus === 'completed')
                            {{ $isDryRun ? 'Preview complete' : 'Import complete' }}
                        @elseif ($processStatus === 'failed')
                            Import failed
                        @endif
                    </flux:heading>
                    <flux:subheading>
                        @if ($isDryRun && $processStatus !== 'completed')
                            Validating rows in dry-run mode. No records will be written until you confirm.
                        @elseif ($isDryRun && $processStatus === 'completed')
                            Review the counts below. Confirm to apply the import to your database.
                        @elseif (! $isDryRun && $processStatus === 'completed')
                            Records have been written to the database.
                        @else
                            Writing records to the database.
                        @endif
                    </flux:subheading>
                </div>

                {{-- Progress bar — visible in every state so completion shows 100% --}}
                @php
                    $barColor = match ($processStatus) {
                        'completed' => 'bg-emerald-500',
                        'failed' => 'bg-red-500',
                        default => 'bg-mq-orange-500',
                    };
                @endphp
                <div class="space-y-2">
                    <div class="flex items-baseline justify-between text-sm">
                        <span class="text-zinc-500">
                            {{ number_format($createdCount + $updatedCount + $skippedCount + $errorCount) }}
                            of {{ number_format($totalDataRows) }} rows
                        </span>
                        <span class="font-mono font-medium">{{ $processPercent }}%</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <div class="h-full {{ $barColor }} transition-all duration-300"
                            style="width: {{ $processPercent }}%"></div>
                    </div>
                </div>

                {{-- Counts grid --}}
                <div class="grid grid-cols-4 gap-3">
                    <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/50 p-3 text-center">
                        <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($createdCount) }}</div>
                        <div class="text-xs text-zinc-500 mt-1">Created</div>
                    </div>
                    <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/50 p-3 text-center">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($updatedCount) }}</div>
                        <div class="text-xs text-zinc-500 mt-1">Updated</div>
                    </div>
                    <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/50 p-3 text-center">
                        <div class="text-2xl font-bold text-zinc-600 dark:text-zinc-400">{{ number_format($skippedCount) }}</div>
                        <div class="text-xs text-zinc-500 mt-1">Skipped</div>
                    </div>
                    <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/50 p-3 text-center">
                        <div class="text-2xl font-bold {{ $errorCount > 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-400' }}">{{ number_format($errorCount) }}</div>
                        <div class="text-xs text-zinc-500 mt-1">Errors</div>
                    </div>
                </div>

                {{-- Action footer --}}
                <div class="flex justify-between gap-2 pt-2">
                    <div>
                        @if ($errorFileUrl)
                            <flux:button type="button" variant="ghost" icon="arrow-down-tray" :href="$errorFileUrl">
                                Download error rows
                            </flux:button>
                        @endif
                    </div>

                    <div class="flex gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">
                                {{ $processStatus === 'completed' && ! $isDryRun ? 'Done' : 'Close' }}
                            </flux:button>
                        </flux:modal.close>

                        @if ($isDryRun && $processStatus === 'completed')
                            <flux:button type="button" variant="primary" icon="check" wire:click="executeForReal">
                                Confirm &amp; import
                            </flux:button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </flux:modal>
</div>
