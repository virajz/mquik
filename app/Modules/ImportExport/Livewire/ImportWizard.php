<?php

namespace App\Modules\ImportExport\Livewire;

use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\ImportExport\Jobs\ProcessImportJob;
use App\Modules\ImportExport\Models\Import;
use App\Modules\ImportExport\Support\UploadHandler;
use App\Support\ModuleRegistry;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class ImportWizard extends Component
{
    use WithFileUploads;

    public string $module;

    public int $userId = 0;

    /** Wizard step: 1=upload, 2=map, 3=behavior, 4=processing/results */
    public int $step = 1;

    public bool $isDryRun = true;

    /** processStatus: idle | processing | completed | failed */
    public string $processStatus = 'idle';

    public int $processPercent = 0;

    public int $createdCount = 0;

    public int $updatedCount = 0;

    public int $skippedCount = 0;

    public int $errorCount = 0;

    public ?string $errorFileUrl = null;

    public ?TemporaryUploadedFile $file = null;

    public ?int $importId = null;

    /** @var array<int, string> */
    public array $csvHeaders = [];

    /** @var array<int, array<int, string>> */
    public array $previewRows = [];

    public int $totalDataRows = 0;

    /** target_column => csv_header_name (or null) */
    public array $mapping = [];

    public string $duplicateBehavior = 'upsert';   // upsert | create_only | skip | error

    public string $errorBehavior = 'skip';         // skip | halt

    public function mount(string $module): void
    {
        $this->module = $module;
        $this->userId = (int) auth()->id();
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240', 'mimetypes:text/csv,text/plain', 'mimes:csv,txt'],
        ];
    }

    #[On('start-import')]
    public function maybeStart(string $module): void
    {
        if ($module !== $this->module) {
            return;
        }

        // Gate: require the master's *.import permission. Slug derived from module
        // folder name (CustomerMaster → customer_master.import).
        $this->authorize(Str::snake($this->module).'.import');

        if (! $this->resolveImporter()) {
            Flux::toast(text: 'This module is not importable yet.', variant: 'danger');

            return;
        }

        $this->reset(['file', 'importId', 'csvHeaders', 'previewRows', 'totalDataRows', 'mapping']);
        $this->step = 1;
        $this->duplicateBehavior = 'upsert';
        $this->errorBehavior = 'skip';

        Flux::modal('import-wizard-'.$this->module)->show();
    }

    /** STEP 1 → STEP 2 */
    public function processUpload(): void
    {
        $this->validate();

        $importer = $this->resolveImporter();
        if (! $importer) {
            return;
        }

        $result = UploadHandler::handle($this->file, $importer, $this->module, $this->userId);

        $this->importId = $result['import']->id;
        $this->csvHeaders = $result['headers'];
        $this->previewRows = $result['preview_rows'];
        $this->totalDataRows = $result['total_rows'];
        $this->mapping = $result['suggested_map'];
        $this->step = 2;
    }

    /** STEP 2 → STEP 3 */
    public function confirmMapping(): void
    {
        $importer = $this->resolveImporter();
        if (! $importer) {
            return;
        }

        $missing = [];
        foreach ($importer->columns() as $col => $meta) {
            if (($meta['required'] ?? false) && empty($this->mapping[$col])) {
                $missing[] = $meta['label'] ?? $col;
            }
        }

        if (! empty($missing)) {
            Flux::toast(text: 'Required columns not mapped: '.implode(', ', $missing), variant: 'danger');

            return;
        }

        Import::find($this->importId)?->update(['mapping' => $this->mapping]);
        $this->step = 3;
    }

    /** STEP 3 → STEP 4 (dry-run) */
    public function confirmBehavior(): void
    {
        $this->dispatchProcessing(dryRun: true, andUpdate: [
            'behavior' => ['duplicates' => $this->duplicateBehavior, 'errors' => $this->errorBehavior],
            'dry_run' => true,
        ]);
    }

    /** STEP 4 → re-dispatch as REAL run after dry-run review */
    public function executeForReal(): void
    {
        $this->dispatchProcessing(dryRun: false, andUpdate: ['dry_run' => false]);
    }

    protected function dispatchProcessing(bool $dryRun, array $andUpdate): void
    {
        $import = Import::find($this->importId);
        if (! $import) {
            return;
        }

        $import->update($andUpdate);

        $this->isDryRun = $dryRun;
        $this->processStatus = 'processing';
        $this->processPercent = 0;
        $this->createdCount = 0;
        $this->updatedCount = 0;
        $this->skippedCount = 0;
        $this->errorCount = 0;
        $this->errorFileUrl = null;
        $this->step = 4;

        ProcessImportJob::dispatch($import);
    }

    #[On('echo-private:imports.{userId},.progress')]
    public function onProgress(array $payload): void
    {
        $this->applyPayload($payload);
    }

    #[On('echo-private:imports.{userId},.completed')]
    public function onCompleted(array $payload): void
    {
        if (! $this->applyPayload($payload, isFinal: true)) {
            return;
        }

        $message = $this->isDryRun
            ? 'Preview ready — review the counts and confirm the import.'
            : 'Import complete: '.$this->createdCount.' created, '.$this->updatedCount.' updated.';

        Flux::toast(text: $message, variant: 'success');

        // Real imports modify records — notify the parent Index so the table refreshes.
        // Dry-runs change nothing, so no event is needed.
        if (! $this->isDryRun) {
            $this->dispatch(Str::kebab($this->module).':saved');
        }
    }

    /** Returns true if payload was applied (matched our import id), false otherwise. */
    protected function applyPayload(array $payload, bool $isFinal = false): bool
    {
        if (($payload['id'] ?? null) !== $this->importId) {
            return false;
        }

        $this->processStatus = $isFinal ? 'completed' : ($payload['status'] ?? $this->processStatus);
        $this->processPercent = $isFinal ? 100 : ($payload['percent'] ?? 0);
        $this->createdCount = $payload['created_count'] ?? 0;
        $this->updatedCount = $payload['updated_count'] ?? 0;
        $this->skippedCount = $payload['skipped_count'] ?? 0;
        $this->errorCount = $payload['error_count'] ?? 0;

        if ($isFinal) {
            $this->errorFileUrl = $payload['error_file_url'] ?? null;
            $this->isDryRun = (bool) ($payload['dry_run'] ?? false);
        }

        return true;
    }

    public function back(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function resolveImporter(): ?Importable
    {
        $manifest = app(ModuleRegistry::class)->get($this->module);

        return $manifest && ! empty($manifest['importable'])
            ? app($manifest['importable'])
            : null;
    }

    public function render()
    {
        return view('import-export::import-wizard', [
            'targetColumns' => $this->resolveImporter()?->columns() ?? [],
        ]);
    }
}
