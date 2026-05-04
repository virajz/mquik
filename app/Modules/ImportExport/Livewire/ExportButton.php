<?php

namespace App\Modules\ImportExport\Livewire;

use App\Modules\ImportExport\Jobs\GenerateExportJob;
use App\Modules\ImportExport\Models\Export;
use App\Support\ModuleRegistry;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class ExportButton extends Component
{
    /** Module name (StudlyCase) — passed in from the parent index view. */
    public string $module;

    /** Public property so #[On] attribute can interpolate it into the channel name. */
    public int $userId = 0;

    public ?int $activeExportId = null;

    public string $status = 'idle';   // idle | processing | completed | failed

    public int $percent = 0;

    public ?string $downloadUrl = null;

    public ?string $errorMessage = null;

    public function mount(string $module): void
    {
        $this->module = $module;
        $this->userId = (int) auth()->id();
    }

    /**
     * Triggered globally by the dropdown menu's wire:click="$dispatch('start-export', { module: '...' })".
     * Each ExportButton instance listens but only acts when its $module matches the payload.
     */
    #[On('start-export')]
    public function maybeStart(string $module): void
    {
        if ($module !== $this->module) {
            return;
        }

        $this->start();
    }

    public function start(): void
    {
        $registry = app(ModuleRegistry::class);
        $manifest = $registry->get($this->module);

        if (! $manifest || empty($manifest['exportable'])) {
            Flux::toast(text: 'This module is not exportable yet.', variant: 'danger');

            return;
        }

        $export = Export::create([
            'user_id' => $this->userId,
            'module' => $this->module,
            'format' => 'csv',
            'status' => 'pending',
        ]);

        $this->activeExportId = $export->id;
        $this->status = 'processing';
        $this->percent = 0;
        $this->downloadUrl = null;
        $this->errorMessage = null;

        GenerateExportJob::dispatch($export);

        Flux::modal('export-progress-'.$this->module)->show();
    }

    /**
     * Echo private-channel listeners. {userId} interpolates from the public property at mount time.
     *
     * The leading "." before the event name is REQUIRED — it tells Echo this is a custom
     * broadcastAs() name and not the FQCN of an event class. Without the dot, Echo would
     * silently look for "App\Modules\ImportExport\Events\progress" and never fire.
     */
    #[On('echo-private:exports.{userId},.progress')]
    public function onProgress(array $payload): void
    {
        if (($payload['id'] ?? null) !== $this->activeExportId) {
            return;
        }

        $this->status = $payload['status'] ?? $this->status;
        $this->percent = $payload['percent'] ?? $this->percent;
    }

    #[On('echo-private:exports.{userId},.completed')]
    public function onCompleted(array $payload): void
    {
        if (($payload['id'] ?? null) !== $this->activeExportId) {
            return;
        }

        $this->status = 'completed';
        $this->percent = 100;
        $this->downloadUrl = $payload['download_url'] ?? null;

        Flux::toast(text: 'Export ready — '.($payload['total_rows'] ?? 0).' rows.', variant: 'success');
    }

    public function render()
    {
        return view('import-export::export-button');
    }
}
