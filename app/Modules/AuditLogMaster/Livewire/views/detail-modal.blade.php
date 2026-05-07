@php
    $diff = $log->diff();
@endphp

<flux:modal :name="'audit-log-detail-' . $log->id" class="md:w-[42rem]">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="space-y-2">
            <div class="flex items-center gap-2">
                <flux:badge :color="$eventColors[$log->event] ?? 'zinc'" size="sm">
                    {{ ucfirst($log->event) }}
                </flux:badge>
                <flux:heading size="lg">
                    {{ \Illuminate\Support\Str::headline(class_basename($log->model_type)) }}
                    <span class="font-mono text-sm text-zinc-500">#{{ $log->model_id }}</span>
                </flux:heading>
            </div>

            <div class="text-sm text-zinc-600 dark:text-zinc-300">
                <span class="font-medium">{{ $log->model_label ?? '—' }}</span>
            </div>

            <div class="grid grid-cols-2 gap-4 text-xs text-zinc-500">
                <div>
                    <div class="uppercase tracking-wide text-zinc-400">User</div>
                    <div class="text-zinc-700 dark:text-zinc-200 mt-0.5">
                        {{ $log->user_name ?? 'system' }}
                    </div>
                </div>
                <div>
                    <div class="uppercase tracking-wide text-zinc-400">Timestamp</div>
                    <div class="text-zinc-700 dark:text-zinc-200 mt-0.5 font-mono">
                        {{ $log->created_at->format('Y-m-d H:i:s') }}
                    </div>
                </div>
                <div>
                    <div class="uppercase tracking-wide text-zinc-400">IP Address</div>
                    <div class="text-zinc-700 dark:text-zinc-200 mt-0.5 font-mono">
                        {{ $log->ip_address ?? '—' }}
                    </div>
                </div>
                <div>
                    <div class="uppercase tracking-wide text-zinc-400">Module</div>
                    <div class="text-zinc-700 dark:text-zinc-200 mt-0.5 font-mono break-all">
                        {{ class_basename($log->model_type) }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Diff table --}}
        <div>
            <flux:heading size="sm" class="mb-2">Changes</flux:heading>

            @if (empty($diff))
                <div class="rounded-md bg-zinc-50 dark:bg-zinc-800/50 px-4 py-6 text-center text-sm text-zinc-500">
                    @if ($log->event === 'deleted')
                        Record removed; previous state captured.
                    @elseif ($log->event === 'created')
                        Initial values captured below.
                    @else
                        No field-level differences recorded.
                    @endif
                </div>
            @endif

            @if ($log->event === 'created' && empty($diff) && $log->new_values)
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="w-1/3">Field</flux:table.column>
                        <flux:table.column>Value</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($log->new_values as $field => $value)
                            @if (! in_array($field, ['updated_at', 'created_at'], true))
                                <flux:table.row :key="'new-' . $field">
                                    <flux:table.cell class="font-mono text-xs text-zinc-700 dark:text-zinc-300 align-top">
                                        {{ $field }}
                                    </flux:table.cell>
                                    <flux:table.cell class="align-top">
                                        @if (is_array($value) || is_object($value))
                                            <pre class="text-xs whitespace-pre-wrap break-all text-zinc-700 dark:text-zinc-200">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        @else
                                            <span class="text-sm text-zinc-700 dark:text-zinc-200 break-all">
                                                {{ $value === null ? '—' : (string) $value }}
                                            </span>
                                        @endif
                                    </flux:table.cell>
                                </flux:table.row>
                            @endif
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @elseif (! empty($diff))
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="w-1/4">Field</flux:table.column>
                        <flux:table.column>Old</flux:table.column>
                        <flux:table.column>New</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($diff as $field => $values)
                            <flux:table.row :key="'diff-' . $field">
                                <flux:table.cell class="font-mono text-xs text-zinc-700 dark:text-zinc-300 align-top">
                                    {{ $field }}
                                </flux:table.cell>
                                <flux:table.cell class="align-top">
                                    @if (is_array($values['old']) || is_object($values['old']))
                                        <pre class="text-xs whitespace-pre-wrap break-all text-zinc-600 dark:text-zinc-400">{{ json_encode($values['old'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    @else
                                        <span class="text-sm text-zinc-600 dark:text-zinc-400 break-all">
                                            {{ $values['old'] === null ? '—' : (string) $values['old'] }}
                                        </span>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="align-top">
                                    @if (is_array($values['new']) || is_object($values['new']))
                                        <pre class="text-xs whitespace-pre-wrap break-all text-zinc-700 dark:text-zinc-200">{{ json_encode($values['new'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    @else
                                        <span class="text-sm text-zinc-700 dark:text-zinc-200 break-all">
                                            {{ $values['new'] === null ? '—' : (string) $values['new'] }}
                                        </span>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @elseif ($log->event === 'deleted' && $log->old_values)
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="w-1/3">Field</flux:table.column>
                        <flux:table.column>Final value</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($log->old_values as $field => $value)
                            @if (! in_array($field, ['updated_at', 'created_at'], true))
                                <flux:table.row :key="'old-' . $field">
                                    <flux:table.cell class="font-mono text-xs text-zinc-700 dark:text-zinc-300 align-top">
                                        {{ $field }}
                                    </flux:table.cell>
                                    <flux:table.cell class="align-top">
                                        @if (is_array($value) || is_object($value))
                                            <pre class="text-xs whitespace-pre-wrap break-all text-zinc-700 dark:text-zinc-200">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        @else
                                            <span class="text-sm text-zinc-700 dark:text-zinc-200 break-all">
                                                {{ $value === null ? '—' : (string) $value }}
                                            </span>
                                        @endif
                                    </flux:table.cell>
                                </flux:table.row>
                            @endif
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </div>

        <div class="flex justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">Close</flux:button>
            </flux:modal.close>
        </div>
    </div>
</flux:modal>
