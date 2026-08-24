<?php

namespace App\Concerns;

use App\Modules\JobDescriptionMaster\Models\JobDescriptionMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * Quick-add for the jobs a department books over and over.
 *
 * Most bookings are the same handful of jobs, so the frequent job descriptions
 * for the chosen department are offered as a checklist, grouped under their
 * service type with the heading ticking the whole group — a standard periodic
 * service is one click rather than five.
 *
 * These are deliberately NOT customer complaints. A complaint is what the
 * customer said in their own words; these are the jobs we agreed to do. They
 * live in `appointment_services` so ticking a box never manufactures a
 * complaint row nobody wrote.
 *
 * Which department a job description belongs to is read through its service
 * type, which already carries the department — no second link to disagree with.
 */
trait PicksQuickServices
{
    /** Ticked job-description ids from the checklist. */
    public array $selectedServiceIds = [];

    /** Ids picked from the Requested Repairs dropdown (anything not frequent). */
    public array $requestedRepairIds = [];

    /** Free-typed jobs the master does not have yet. */
    public array $manualRepairs = [];

    public string $manualRepairInput = '';

    /**
     * Frequent job descriptions for this department, grouped by service type.
     *
     * @return Collection<string, Collection<int, JobDescriptionMaster>>
     */
    #[Computed]
    public function frequentServiceGroups(): Collection
    {
        return $this->jobDescriptionsForDepartment('frequent')
            ->groupBy(fn (JobDescriptionMaster $jd) => $jd->serviceType?->name ?? 'Other');
    }

    /** @return Collection<int, JobDescriptionMaster> */
    #[Computed]
    public function otherJobDescriptions(): Collection
    {
        return $this->jobDescriptionsForDepartment('general');
    }

    /** Everything booked, across the checklist, the dropdown and typed lines. */
    public function selectedServiceCount(): int
    {
        return count($this->selectedServiceIds)
            + count($this->requestedRepairIds)
            + count($this->manualRepairs);
    }

    public function isServiceSelected(int $jobDescriptionId): bool
    {
        return in_array($jobDescriptionId, array_map('intval', $this->selectedServiceIds), true);
    }

    /** Whole group ticked? Drives the heading checkbox's own state. */
    public function isGroupSelected(string $group): bool
    {
        $ids = $this->groupIds($group);

        return $ids !== [] && array_diff($ids, array_map('intval', $this->selectedServiceIds)) === [];
    }

    public function toggleService(int $jobDescriptionId): void
    {
        $current = array_map('intval', $this->selectedServiceIds);

        $this->selectedServiceIds = array_map('strval', $this->isServiceSelected($jobDescriptionId)
            ? array_values(array_diff($current, [$jobDescriptionId]))
            : array_values(array_unique([...$current, $jobDescriptionId])));
    }

    /** Tick every job in the group, or clear them all if they are already ticked. */
    public function toggleGroup(string $group): void
    {
        $ids = $this->groupIds($group);
        $current = array_map('intval', $this->selectedServiceIds);

        $this->selectedServiceIds = array_map('strval', $this->isGroupSelected($group)
            ? array_values(array_diff($current, $ids))
            : array_values(array_unique([...$current, ...$ids])));
    }

    /** Add a job the master does not carry, typed by the advisor. */
    public function addManualRepair(): void
    {
        $name = strtoupper(trim($this->manualRepairInput));

        if ($name === '' || in_array($name, $this->manualRepairs, true)) {
            $this->manualRepairInput = '';

            return;
        }

        $this->manualRepairs[] = $name;
        $this->manualRepairInput = '';
    }

    public function removeManualRepair(int $index): void
    {
        unset($this->manualRepairs[$index]);
        $this->manualRepairs = array_values($this->manualRepairs);
    }

    /**
     * Saved service rows for the record being edited — each with a
     * `job_description_id` and `name`. The host component provides them.
     *
     * @return \Illuminate\Support\Collection<int, Model>
     */
    abstract protected function savedServiceRows(): \Illuminate\Support\Collection;

    /** Label for the checklist heading. */
    #[Computed]
    public function departmentName(): string
    {
        return $this->workshop_department_id
            ? (string) WorkshopDepartmentMaster::whereKey($this->workshop_department_id)->value('name')
            : '';
    }

    /** Load an existing record's jobs back into the three inputs. */
    protected function seedSelectedServices(): void
    {
        $this->fillServiceInputsFrom($this->savedServiceRows());
    }

    /**
     * Split service rows into the three inputs — also used to inherit another
     * record's jobs (a pickup created from an appointment carries its booking).
     *
     * @param  \Illuminate\Support\Collection<int, Model>  $rows
     */
    protected function fillServiceInputsFrom(\Illuminate\Support\Collection $rows): void
    {

        $frequent = $this->frequentServiceGroups->flatten()->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->selectedServiceIds = [];
        $this->requestedRepairIds = [];
        $this->manualRepairs = [];

        foreach ($rows as $row) {
            if ($row->job_description_id === null) {
                $this->manualRepairs[] = $row->name;

                continue;
            }

            in_array((int) $row->job_description_id, $frequent, true)
                ? $this->selectedServiceIds[] = (string) $row->job_description_id
                : $this->requestedRepairIds[] = (string) $row->job_description_id;
        }
    }

    /**
     * Rewrite the booking's job list. Small and fully replaced each save — these
     * rows carry no state of their own worth preserving.
     */
    protected function syncSelectedServices(Model $record): void
    {
        $names = JobDescriptionMaster::whereIn('id', [...$this->selectedServiceIds, ...$this->requestedRepairIds])
            ->pluck('name', 'id');

        $rows = [];
        $seq = 0;

        foreach ([...$this->selectedServiceIds, ...$this->requestedRepairIds] as $id) {
            if (! isset($names[$id])) {
                continue;
            }

            $rows[] = ['job_description_id' => (int) $id, 'name' => $names[$id], 'sequence_no' => $seq++];
        }

        foreach ($this->manualRepairs as $name) {
            $rows[] = ['job_description_id' => null, 'name' => $name, 'sequence_no' => $seq++];
        }

        $record->services()->delete();
        $record->services()->createMany($rows);
    }

    /** Drop anything the newly chosen department does not offer. */
    protected function dropServicesOutsideDepartment(): void
    {
        $frequent = $this->frequentServiceGroups->flatten()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $other = $this->otherJobDescriptions->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->selectedServiceIds = array_map('strval', array_values(array_intersect(array_map('intval', $this->selectedServiceIds), $frequent)));
        $this->requestedRepairIds = array_map('strval', array_values(array_intersect(array_map('intval', $this->requestedRepairIds), $other)));
    }

    /** @return list<int> */
    protected function groupIds(string $group): array
    {
        return $this->frequentServiceGroups->get($group)?->pluck('id')->map(fn ($id) => (int) $id)->all() ?? [];
    }

    /** @return Collection<int, JobDescriptionMaster> */
    protected function jobDescriptionsForDepartment(string $category): Collection
    {
        if (! $this->workshop_department_id) {
            return collect();
        }

        return JobDescriptionMaster::query()
            ->where('is_active', true)
            ->where('category', $category)
            ->whereHas('serviceType', fn ($q) => $q->where('workshop_department_id', $this->workshop_department_id))
            ->with('serviceType:id,name')
            ->orderBy('name')
            ->get();
    }
}
