<?php

namespace App\Livewire\Settings;

use App\Modules\JobCard\Support\ServiceHistorySort;
use App\Support\AppSettings;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Workshop-wide settings that used to live in `.env`.
 */
#[Layout('layouts.app')]
#[Title('Workshop Settings')]
class Workshop extends Component
{
    public ?int $servicesShown = null;

    public ?int $visitsScanned = null;

    public ?int $visitsListed = null;

    public ?int $defaultOverdueMonths = null;

    public string $sortMode = 'due_first';

    public ?int $sessionLifetime = null;

    public function mount(): void
    {
        $this->servicesShown = AppSettings::int('service_history.services_shown');
        $this->visitsScanned = AppSettings::int('service_history.visits_scanned');
        $this->visitsListed = AppSettings::int('service_history.visits_listed');
        $this->defaultOverdueMonths = AppSettings::int('service_history.default_overdue_months');
        $this->sortMode = (string) AppSettings::get('service_history.sort_mode', 'due_first');
        $this->sessionLifetime = AppSettings::int('security.session_lifetime') ?: config('session.lifetime');
    }

    protected function rules(): array
    {
        return [
            'servicesShown' => ['required', 'integer', 'min:1', 'max:50'],
            'visitsScanned' => ['required', 'integer', 'min:10', 'max:500'],
            'visitsListed' => ['required', 'integer', 'min:5', 'max:200'],
            // Blank means "never flag a service that has no interval of its own".
            'defaultOverdueMonths' => ['nullable', 'integer', 'min:1', 'max:120'],
            'sortMode' => ['required', Rule::in(array_keys(ServiceHistorySort::options()))],
            // Long enough to be usable, short enough to matter on a shared floor terminal.
            'sessionLifetime' => ['required', 'integer', 'min:5', 'max:480'],
        ];
    }

    public function save(): void
    {
        $this->authorize('company_master.settings');

        $this->validate();

        AppSettings::set('service_history.services_shown', $this->servicesShown);
        AppSettings::set('service_history.visits_scanned', $this->visitsScanned);
        AppSettings::set('service_history.visits_listed', $this->visitsListed);
        AppSettings::set('service_history.default_overdue_months', $this->defaultOverdueMonths);
        AppSettings::set('service_history.sort_mode', $this->sortMode);
        AppSettings::set('security.session_lifetime', $this->sessionLifetime);

        Flux::toast(text: 'Settings saved.', variant: 'success');
    }

    /** @return array<string, string> */
    public function sortModes(): array
    {
        return ServiceHistorySort::options();
    }

    public function render()
    {
        return view('livewire.settings.workshop');
    }
}
