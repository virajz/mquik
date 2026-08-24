<div>
    <form wire:submit="save" class="max-w-7xl">
        {{-- HEADER --}}
        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <flux:link :href="route('document-collection.index')" variant="ghost" class="text-xs">
                    <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Document Collection
                </flux:link>
                <flux:heading size="xl" level="1" class="mt-1">
                    {{ $editingId ? $doc_collection_no : 'New Document Collection' }}
                </flux:heading>
            </div>
            @if ($editingId)
                <flux:badge :color="match ($status) {
                    'pending' => 'amber', 'requested' => 'blue', 'received' => 'lime',
                    'rejected' => 'red', 'cancelled' => 'zinc', default => 'zinc',
                }" size="lg">{{ \App\Modules\DocumentCollection\Models\DocumentCollection::statuses()[$status] ?? $status }}</flux:badge>
            @endif
        </div>

        <flux:separator />

        @if (! $editingId)
            {{-- LEAN CREATE --}}
            @include('document-collection::partials.section-details', ['lean' => true])
            <div class="mt-6 flex items-start gap-3 rounded-lg border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-sm text-zinc-500">
                <flux:icon.lock-closed class="size-5 shrink-0 text-zinc-400" />
                <span>The document checklist, file attachments and verification unlock once you create — you'll land on the full editor with the chosen checklist items ready to receive.</span>
            </div>
        @else
            {{-- RICH EDIT — TABS --}}
            <flux:tab.group class="mt-6">
                <flux:tabs wire:model="activeTab">
                    <flux:tab name="details" icon="identification">Details</flux:tab>
                    <flux:tab name="documents" icon="document-text">Documents ({{ count($items) }})</flux:tab>
                    <flux:tab name="verification" icon="check-badge">Verification &amp; Sign-off</flux:tab>
                </flux:tabs>

                <flux:tab.panel name="details">
                    @include('document-collection::partials.section-details', ['lean' => false])
                </flux:tab.panel>

                {{-- DOCUMENTS --}}
                <flux:tab.panel name="documents">
                    <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-6">
                        <div>
                            <flux:heading size="lg">Document Checklist</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Mark each document Received / Rejected and attach the scan (JPG / PNG / PDF).</flux:text>
                        </div>
                        <div class="space-y-3 min-w-0">
                            <div class="flex justify-end">
                                <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem">Add document</flux:button>
                            </div>

                            @if (count($items) === 0)
                                <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                                    No documents yet. Pick a Document Checklist in the Details tab, or click <span class="font-medium">Add document</span>.
                                </div>
                            @else
                                @foreach ($items as $i => $row)
                                    <div wire:key="dc-item-{{ $i }}" class="rounded-md border border-zinc-200 dark:border-zinc-800 p-3 space-y-2">
                                        <div class="grid grid-cols-1 md:grid-cols-[1fr_150px_40px] gap-2 items-end">
                                            <flux:input wire:model="items.{{ $i }}.label" size="sm" label="Document" placeholder="e.g. RC BOOK" required />
                                            <flux:select wire:model.live="items.{{ $i }}.status" variant="listbox" size="sm" label="Status">
                                                @foreach (\App\Modules\DocumentCollection\Models\DocumentCollectionItem::statuses() as $key => $label)
                                                    <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                            <flux:button type="button" size="sm" variant="ghost" icon="x-mark" wire:click="removeItem({{ $i }})" class="h-9!" />
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 items-center">
                                            <div>
                                                @if (($row['status'] ?? '') === 'received' && ! empty($row['received_at']))
                                                    <div class="text-xs text-lime-700 dark:text-lime-400 mb-1">Received {{ \Illuminate\Support\Carbon::parse($row['received_at'])->format('d M Y, h:i A') }}</div>
                                                @endif
                                                @if (! empty($row['path']))
                                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($row['path']) }}" target="_blank" class="inline-flex items-center gap-1 text-xs text-mq-orange-600 hover:underline">
                                                        <flux:icon.paper-clip class="size-3.5" /> {{ $row['original_name'] ?? 'View file' }}
                                                    </a>
                                                @endif
                                                <flux:input type="file" wire:model="itemFiles.{{ $i }}" accept="image/*,application/pdf" size="sm" />
                                                <flux:error name="itemFiles.{{ $i }}" />
                                            </div>
                                            @if (($row['status'] ?? '') === 'rejected')
                                                <flux:select wire:model="items.{{ $i }}.rejection_reason_id" variant="listbox" searchable clearable size="sm" placeholder="Rejection reason…">
                                                    @foreach ($this->rejectionReasons as $rr)
                                                        <flux:select.option :value="$rr->id" wire:key="dc-item-rr-{{ $i }}-{{ $rr->id }}">{{ $rr->name }}</flux:select.option>
                                                    @endforeach
                                                </flux:select>
                                            @else
                                                <flux:input wire:model="items.{{ $i }}.notes" size="sm" placeholder="Notes (optional)" />
                                            @endif
                                        </div>
                                        <flux:error name="items.{{ $i }}.label" />
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </section>
                </flux:tab.panel>

                {{-- VERIFICATION & SIGN-OFF --}}
                <flux:tab.panel name="verification">
                    <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-6">
                        <div>
                            <flux:heading size="lg">Verification Checklist</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Confirm each verification check passed.</flux:text>
                        </div>
                        <div class="space-y-2 min-w-0">
                            <div class="flex justify-end">
                                <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addVerification">Add check</flux:button>
                            </div>
                            @forelse ($verifications as $i => $v)
                                <div wire:key="dc-verif-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[24px_1fr_1fr_40px] gap-2 items-center py-1.5 border-b border-zinc-100 dark:border-zinc-800/60 last:border-0">
                                    <flux:checkbox wire:model="verifications.{{ $i }}.is_verified" />
                                    <flux:input wire:model="verifications.{{ $i }}.label" size="sm" placeholder="Check (e.g. NAME MATCH)" required />
                                    <flux:input wire:model="verifications.{{ $i }}.notes" size="sm" placeholder="Notes (optional)" />
                                    <flux:button type="button" size="sm" variant="ghost" icon="x-mark" wire:click="removeVerification({{ $i }})" />
                                </div>
                            @empty
                                <flux:text size="sm" class="text-zinc-500">Pick a Verification Checklist in Details, or add checks manually.</flux:text>
                            @endforelse
                        </div>
                    </section>

                    <flux:separator />

                    <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-6">
                        <div>
                            <flux:heading size="lg">Follow-up & Outcome</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">If documents are missing or rejected, capture why and how you'll follow up.</flux:text>
                        </div>
                        <div class="space-y-4 min-w-0">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <flux:select wire:model="missing_document_reason_id" variant="listbox" searchable clearable label="Missing Document Reason" placeholder="If missing…">
                                    @foreach ($this->missingReasons as $mr)
                                        <flux:select.option :value="$mr->id" wire:key="dc-mr-{{ $mr->id }}">{{ $mr->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="rejection_reason_id" variant="listbox" searchable clearable label="Rejection Reason" placeholder="If rejected…">
                                    @foreach ($this->rejectionReasons as $rr)
                                        <flux:select.option :value="$rr->id" wire:key="dc-prr-{{ $rr->id }}">{{ $rr->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="follow_up_mode_id" variant="listbox" searchable clearable label="Follow-up Mode" placeholder="Call / SMS / WhatsApp…">
                                    @foreach ($this->followUpModes as $fm)
                                        <flux:select.option :value="$fm->id" wire:key="dc-fm-{{ $fm->id }}">{{ $fm->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                            {{-- The chase log: every attempt, its channel, and what
                                 the customer actually said. --}}
                            <flux:field>
                                <flux:label>Follow-up Log</flux:label>
                                <div class="space-y-2">
                                    @forelse ($followUps as $i => $fu)
                                        <div class="grid grid-cols-1 md:grid-cols-[190px_1fr_1fr_1fr_auto] gap-2 items-end" wire:key="dc-fu-{{ $i }}">
                                            <flux:input type="datetime-local" wire:model="followUps.{{ $i }}.followed_up_at" size="sm" label="When" />
                                            <flux:select wire:model="followUps.{{ $i }}.followed_up_by_id" variant="listbox" searchable clearable size="sm" label="By" placeholder="Who called…">
                                                @foreach ($this->employees as $e)
                                                    <flux:select.option :value="$e->id" wire:key="dc-fu-by-{{ $i }}-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                            <flux:select wire:model="followUps.{{ $i }}.follow_up_mode_id" variant="listbox" clearable size="sm" label="Mode" placeholder="Call / SMS…">
                                                @foreach ($this->followUpModes as $fm)
                                                    <flux:select.option :value="$fm->id" wire:key="dc-fu-md-{{ $i }}-{{ $fm->id }}">{{ $fm->name }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                            <flux:input wire:model="followUps.{{ $i }}.customer_response" size="sm" label="Customer Response" placeholder="Will send by Friday…" />
                                            <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeFollowUp({{ $i }})" class="h-9!" />
                                        </div>
                                    @empty
                                        <flux:text size="sm" class="text-zinc-500">No follow-ups recorded yet.</flux:text>
                                    @endforelse
                                    <flux:button type="button" variant="ghost" icon="plus" size="sm" wire:click="addFollowUp">Add follow-up</flux:button>
                                </div>
                            </flux:field>

                            {{-- What the advisor actually sends: the outstanding list,
                                 ready to paste or fire off on WhatsApp. --}}
                            <flux:field>
                                <flux:label>Request Message</flux:label>
                                <div class="rounded-md border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 p-3 text-sm whitespace-pre-line font-mono">{{ $this->requestMessage }}</div>
                                <div class="flex items-center gap-2 mt-2" x-data="{ copied: false }">
                                    <flux:button size="sm" variant="outline" icon="clipboard"
                                        x-on:click="navigator.clipboard.writeText(@js($this->requestMessage)); copied = true; setTimeout(() => copied = false, 2000)">
                                        Copy message
                                    </flux:button>
                                    @if ($this->whatsAppUrl)
                                        <flux:button size="sm" variant="outline" icon="chat-bubble-left-ellipsis" :href="$this->whatsAppUrl" target="_blank">
                                            Send on WhatsApp
                                        </flux:button>
                                    @endif
                                    <span x-show="copied" x-cloak class="text-xs text-lime-600 dark:text-lime-400">Copied!</span>
                                </div>
                            </flux:field>

                            <flux:textarea wire:model="notes" label="Notes" placeholder="Anything worth recording about this collection." rows="2" />
                        </div>
                    </section>

                    <flux:separator />

                    <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-6">
                        <div>
                            <flux:heading size="lg">Customer Signature</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Acknowledgement signature on document handover.</flux:text>
                        </div>
                        <div class="space-y-4 min-w-0">
                            @php $existingSig = $editingId ? \App\Modules\DocumentCollection\Models\DocumentCollection::whereKey($editingId)->value('customer_signature_path') : null; @endphp
                            @if ($existingSig && ! $clearSignature)
                                <div class="flex items-start gap-3">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($existingSig) }}" alt="Signature" class="h-20 w-auto rounded border border-zinc-200 dark:border-zinc-800 bg-white p-1" />
                                    <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="markClearSignature">Remove</flux:button>
                                </div>
                            @endif
                            <flux:file-upload wire:model="signatureUpload" accept="image/*">
                                <flux:file-upload.dropzone icon="pencil-square" :heading="$existingSig && ! $clearSignature ? 'Replace signature' : 'Upload customer signature'" text="PNG / JPG up to 2 MB" />
                            </flux:file-upload>
                            @if ($signatureUpload)
                                <img src="{{ $signatureUpload->temporaryUrl() }}" alt="" class="h-16 w-auto rounded border border-zinc-200 dark:border-zinc-800 bg-white p-1" />
                            @endif
                            <flux:error name="signatureUpload" />
                        </div>
                    </section>
                </flux:tab.panel>
            </flux:tab.group>
        @endif

        {{-- STICKY ACTION BAR --}}
        <div class="sticky bottom-0 z-10 mt-8 flex items-center justify-end gap-2 border-t border-zinc-200 dark:border-zinc-800 bg-white/95 dark:bg-zinc-900/95 py-4 backdrop-blur">
            <flux:button :href="route('document-collection.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save Changes' : 'Create Collection' }}</flux:button>
        </div>
    </form>

    @include('customer-master::_quick_add_modal')
    @include('partials.quick-add-customer-vehicle-modal')
</div>
