<?php

namespace App\Modules\Attendance\Livewire;

use App\Modules\Attendance\Models\Attendance;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    public ?int $editingId = null;

    public ?int $employee_id = null;

    public string $punched_date = '';

    public string $punched_time = '';

    public string $type = Attendance::TYPE_IN;

    public ?TemporaryUploadedFile $selfie = null;

    public bool $clearSelfie = false;

    public ?string $existing_selfie_path = null;

    public ?string $latitude = null;

    public ?string $longitude = null;

    public ?string $notes = null;

    #[On('attendance:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            $now = now();
            $this->punched_date = $now->format('Y-m-d');
            $this->punched_time = $now->format('H:i');

            return;
        }

        $record = Attendance::findOrFail($id);
        $this->editingId = $record->id;
        $this->employee_id = $record->employee_id;
        $this->punched_date = $record->punched_at->format('Y-m-d');
        $this->punched_time = $record->punched_at->format('H:i');
        $this->type = $record->type;
        $this->existing_selfie_path = $record->selfie_path;
        $this->latitude = $record->latitude !== null ? (string) $record->latitude : null;
        $this->longitude = $record->longitude !== null ? (string) $record->longitude : null;
        $this->notes = $record->notes;
    }

    protected function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'punched_date' => ['required', 'date_format:Y-m-d'],
            'punched_time' => ['required', 'date_format:H:i'],
            'type' => ['required', Rule::in(array_keys(Attendance::types()))],
            'selfie' => ['nullable', 'image', 'max:4096'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function markClearSelfie(): void
    {
        $this->clearSelfie = true;
        $this->selfie = null;
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function save(): void
    {
        $data = $this->validate();

        $data['punched_at'] = Carbon::parse($data['punched_date'].' '.$data['punched_time'].':00');
        unset($data['punched_date'], $data['punched_time'], $data['selfie']);

        if (filled($data['notes'] ?? null)) {
            $data['notes'] = strtoupper($data['notes']);
        }

        if ($this->editingId) {
            $record = Attendance::findOrFail($this->editingId);
            $this->persistSelfie($record);
            $record->update($data);
            Flux::toast(text: 'Attendance #'.$record->id.' updated.', variant: 'success');
        } else {
            $record = Attendance::create($data);
            $this->persistSelfie($record);
            Flux::toast(text: 'Attendance #'.$record->id.' created.', variant: 'success');
        }

        $this->dispatch('attendance:saved');
        $this->resetForm();
        Flux::modal('attendance-form')->close();
    }

    protected function persistSelfie(Attendance $record): void
    {
        if ($this->clearSelfie && $record->selfie_path) {
            Storage::disk('public')->delete($record->selfie_path);
            $record->forceFill(['selfie_path' => null])->save();
        }

        if ($this->selfie instanceof TemporaryUploadedFile) {
            if ($record->selfie_path) {
                Storage::disk('public')->delete($record->selfie_path);
            }
            $path = $this->selfie->store("attendance/{$record->employee_id}/selfies", 'public');
            $record->forceFill(['selfie_path' => $path])->save();
        }
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->employee_id = null;
        $this->punched_date = '';
        $this->punched_time = '';
        $this->type = Attendance::TYPE_IN;
        $this->selfie = null;
        $this->clearSelfie = false;
        $this->existing_selfie_path = null;
        $this->latitude = null;
        $this->longitude = null;
        $this->notes = null;
    }

    public function render()
    {
        return view('attendance::form');
    }
}
