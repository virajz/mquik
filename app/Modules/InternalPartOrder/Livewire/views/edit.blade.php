@use(App\Modules\InternalPartOrder\Models\InternalPartOrder)
<div class="max-w-7xl">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:link :href="route('internal-part-order.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Internal Part Orders
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? 'IPO '.$order_no : 'New Internal Part Order' }}</flux:heading>
        </div>
        @if ($editingId)
            <flux:badge :color="match ($status) {
                'fully_issued' => 'lime', 'partially_issued' => 'amber', 'cancelled' => 'red',
                'closed' => 'green', 'approved' => 'blue', default => 'zinc',
            }" size="lg">{{ InternalPartOrder::statuses()[$status] }}</flux:badge>
        @endif
    </div>

    <flux:separator class="mb-6" />

    <form wire:submit="save">
        @if (! $editingId)
            @include('internal-part-order::partials.section-details')
            <flux:callout class="mt-6" icon="information-circle" variant="secondary">
                <flux:callout.text>Create the order first — parts, issue, approval and attachments unlock once it exists.</flux:callout.text>
            </flux:callout>
            <div class="flex items-center justify-end gap-2 py-6">
                <flux:button :href="route('internal-part-order.index')" variant="ghost" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Create IPO</flux:button>
            </div>
        @else
            <flux:tab.group>
                <flux:tabs wire:model="activeTab">
                    <flux:tab name="details" icon="clipboard-document-list">Details</flux:tab>
                    <flux:tab name="parts" icon="cube">Parts &amp; Issue <flux:badge size="sm" class="ml-1">{{ count($items) }}</flux:badge></flux:tab>
                    <flux:tab name="approval" icon="check-badge">Approval &amp; Returns</flux:tab>
                    <flux:tab name="attachments" icon="paper-clip">Attachments</flux:tab>
                </flux:tabs>

                <flux:tab.panel name="details" class="pt-6">
                    @include('internal-part-order::partials.section-details')
                </flux:tab.panel>

                {{-- PARTS & ISSUE --}}
                <flux:tab.panel name="parts" class="pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <flux:heading size="lg">Requested Parts</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Each line: stock status, requested vs issued qty, issue status and photo evidence.</flux:text>
                        </div>
                        <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem">Add part</flux:button>
                    </div>

                    @if (count($items) === 0)
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                            Add the parts being requested from the store.
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($items as $i => $item)
                                <div wire:key="ipo-line-{{ $i }}" class="rounded-lg border border-zinc-200 dark:border-zinc-800 p-3 space-y-3">
                                    <div class="grid grid-cols-1 lg:grid-cols-[1fr_130px_auto] gap-2 items-end">
                                        <flux:select wire:model="items.{{ $i }}.spare_id" variant="listbox" searchable clearable size="sm" label="Spare" placeholder="Pick a spare (or free text)…">
                                            @foreach ($this->spares as $s)
                                                <flux:select.option :value="$s->id" wire:key="sp-{{ $i }}-{{ $s->id }}">{{ $s->name }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:select wire:model="items.{{ $i }}.uom_id" variant="listbox" clearable size="sm" label="UOM">
                                            @foreach ($this->uoms as $u)
                                                <flux:select.option :value="$u->id" wire:key="um-{{ $i }}-{{ $u->id }}">{{ $u->name }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeItem({{ $i }})" />
                                    </div>

                                    <flux:input wire:model="items.{{ $i }}.description" size="sm" placeholder="Description" />

                                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2">
                                        <flux:input type="number" step="0.01" min="0.01" wire:model="items.{{ $i }}.qty_requested" size="sm" label="Req." />
                                        <flux:input type="number" step="0.01" min="0" wire:model="items.{{ $i }}.qty_issued" size="sm" label="Issued" />
                                        <flux:input type="number" step="0.01" min="0" wire:model="items.{{ $i }}.qty_returned" size="sm" label="Returned" />
                                        <flux:select wire:model="items.{{ $i }}.stock_status" variant="listbox" size="sm" label="Stock">
                                            @foreach (InternalPartOrder::stockStatuses() as $k => $l)
                                                <flux:select.option :value="$k" wire:key="ss-{{ $i }}-{{ $k }}">{{ $l }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:select wire:model="items.{{ $i }}.issue_status" variant="listbox" size="sm" label="Issue">
                                            @foreach (InternalPartOrder::issueStatuses() as $k => $l)
                                                <flux:select.option :value="$k" wire:key="is-{{ $i }}-{{ $k }}">{{ $l }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-4">
                                        <flux:checkbox wire:model="items.{{ $i }}.is_alternate" label="Alternate part" />
                                        @foreach (['before' => 'Before', 'after' => 'After'] as $which => $lbl)
                                            @php($path = $item[$which.'_photo_path'] ?? null)
                                            @php($staged = ($which === 'before' ? ($itemBeforeFiles[$i] ?? null) : ($itemAfterFiles[$i] ?? null)))
                                            @php($url = $staged ? $staged->temporaryUrl() : ($path ? \Illuminate\Support\Facades\Storage::disk('public')->url($path) : null))
                                            <div class="flex items-center gap-2">
                                                @if ($url)<img src="{{ $url }}" alt="{{ $lbl }}" class="size-10 rounded object-cover border border-zinc-200 dark:border-zinc-800" />@endif
                                                <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-md border border-zinc-200 dark:border-zinc-700 px-2.5 py-1.5 text-xs font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                                    <input type="file" class="sr-only" wire:model="item{{ ucfirst($which) }}Files.{{ $i }}" accept="image/*" />
                                                    <flux:icon.camera variant="micro" class="size-3.5" /> {{ $lbl }}
                                                </label>
                                                @if ($path)
                                                    <flux:button type="button" size="xs" variant="ghost" icon="trash" wire:click="clearItemPhoto({{ $i }}, '{{ $which }}')" />
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </flux:tab.panel>

                {{-- APPROVAL & RETURNS --}}
                <flux:tab.panel name="approval" class="pt-6">
                    <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
                        <div>
                            <flux:heading size="lg">Approval &amp; Status</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Workflow status, who approves, and reasons for rejection / cancellation.</flux:text>
                        </div>
                        <div class="space-y-4 min-w-0">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <flux:select wire:model="status" variant="listbox" label="IPO Status" required>
                                    @foreach (InternalPartOrder::statuses() as $k => $l)
                                        <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="approval_status" variant="listbox" label="Approval Status" required>
                                    @foreach (InternalPartOrder::approvalStatuses() as $k => $l)
                                        <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <flux:select wire:model="approval_authority" variant="listbox" clearable label="Approval Authority" placeholder="Who approves…">
                                    @foreach (InternalPartOrder::approvalAuthorities() as $k => $l)
                                        <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="approved_by_id" variant="listbox" searchable clearable label="Approved By" placeholder="Employee…">
                                    @foreach ($this->employees as $e)
                                        <flux:select.option :value="$e->id" wire:key="ab-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <flux:select wire:model="rejection_reason_id" variant="listbox" searchable clearable label="Rejection Reason" placeholder="If rejected…">
                                    @foreach ($this->rejectionReasons as $r)
                                        <flux:select.option :value="$r->id" wire:key="rr-{{ $r->id }}">{{ $r->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="cancellation_reason_id" variant="listbox" searchable clearable label="Cancellation Reason" placeholder="If cancelled…">
                                    @foreach ($this->cancellationReasons as $r)
                                        <flux:select.option :value="$r->id" wire:key="cr-{{ $r->id }}">{{ $r->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>

                            <flux:separator variant="subtle" />
                            <flux:heading size="sm">Returns (per line)</flux:heading>
                            @if (count($items) === 0)
                                <flux:text size="sm" class="text-zinc-500">Add parts first.</flux:text>
                            @else
                                <div class="space-y-2">
                                    @foreach ($items as $i => $item)
                                        <div wire:key="ipo-ret-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_180px_160px] gap-2 items-end p-2 rounded-md border border-zinc-200 dark:border-zinc-800">
                                            <div class="text-sm font-medium truncate">{{ $item['description'] ?: '(line '.($i + 1).')' }}</div>
                                            <flux:select wire:model="items.{{ $i }}.return_type_id" variant="listbox" clearable size="sm" label="Return Type">
                                                @foreach ($this->returnTypes as $rt)
                                                    <flux:select.option :value="$rt->id" wire:key="rt-{{ $i }}-{{ $rt->id }}">{{ $rt->name }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                            <flux:select wire:model="items.{{ $i }}.return_status" variant="listbox" clearable size="sm" label="Return Status">
                                                @foreach (InternalPartOrder::returnStatuses() as $k => $l)
                                                    <flux:select.option :value="$k" wire:key="rs-{{ $i }}-{{ $k }}">{{ $l }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Notes for the store / advisor." />
                        </div>
                    </section>
                </flux:tab.panel>

                {{-- ATTACHMENTS --}}
                <flux:tab.panel name="attachments" class="pt-6">
                    <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
                        <div>
                            <flux:heading size="lg">Attachments</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Supporting PDFs or images (approval notes, photos, quotes).</flux:text>
                        </div>
                        <div class="space-y-4 min-w-0">
                            <flux:file-upload wire:model="attachmentFiles" multiple accept=".pdf,image/*">
                                <flux:file-upload.dropzone heading="Drop files here or click to browse" text="PDF, JPG, PNG up to 8MB" />
                            </flux:file-upload>

                            @if ($this->existingAttachments->isNotEmpty() || count($attachmentFiles) > 0)
                                <div class="flex flex-col gap-2">
                                    @foreach ($this->existingAttachments as $att)
                                        <flux:file-item wire:key="att-{{ $att->id }}" :heading="$att->original_name ?? 'File #'.$att->id" :size="$att->size_bytes ?? 0">
                                            <x-slot name="actions">
                                                <flux:file-item.remove wire:click="removeAttachment({{ $att->id }})" />
                                            </x-slot>
                                        </flux:file-item>
                                    @endforeach
                                    @foreach ($attachmentFiles as $i => $file)
                                        <flux:file-item wire:key="att-staged-{{ $i }}" :heading="$file->getClientOriginalName()" :size="$file->getSize()" />
                                    @endforeach
                                </div>
                            @endif
                            <flux:error name="attachmentFiles.*" />
                        </div>
                    </section>
                </flux:tab.panel>
            </flux:tab.group>

            <flux:separator class="mt-4" />
            <div class="flex items-center justify-end gap-2 py-6">
                <flux:button :href="route('internal-part-order.index')" variant="ghost" wire:navigate>Close</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Save Changes</flux:button>
            </div>
        @endif
    </form>
</div>
