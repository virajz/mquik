<?php

namespace App\Modules\Appointment\Concerns;

use App\Concerns\HasQuickCreate;
use App\Modules\RegionMaster\Models\RegionMaster as Region;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * State → city → area pickers for the pickup and drop addresses.
 *
 * Both legs behave identically, so they share one implementation keyed by leg.
 * Only the leaf is persisted (`{leg}_region_id`) — city and state are recovered
 * by walking that region's parents, so nothing can drift out of agreement.
 *
 * Cities and areas are creatable inline: the master will never be complete for
 * a country's worth of localities, and a coordinator with a customer on the
 * phone cannot go and edit the region master first. States are deliberately not
 * creatable — that list is closed and seeded, so a typed one is a typo.
 */
trait PicksAddressRegions
{
    use HasQuickCreate;

    public ?int $pickup_region_id = null;

    public ?int $pickup_state_id = null;

    public ?int $pickup_city_id = null;

    public ?int $drop_state_id = null;

    public ?int $drop_city_id = null;

    /** Combobox text, read by `quickCreate()` when the create-option is clicked. */
    public string $pickupCitySearch = '';

    public string $pickupAreaSearch = '';

    public string $dropCitySearch = '';

    public string $dropAreaSearch = '';

    public function updatedPickupStateId(): void
    {
        $this->clearBelowState('pickup');
    }

    public function updatedDropStateId(): void
    {
        $this->clearBelowState('drop');
    }

    public function updatedPickupCityId(): void
    {
        // A city is a usable region on its own; an area only refines it.
        $this->pickup_region_id = $this->pickup_city_id;
    }

    public function updatedDropCityId(): void
    {
        $this->drop_region_id = $this->drop_city_id;
    }

    public function createPickupCity(): void
    {
        $this->createCity('pickup');
    }

    public function createDropCity(): void
    {
        $this->createCity('drop');
    }

    public function createPickupArea(): void
    {
        $this->createArea('pickup');
    }

    public function createDropArea(): void
    {
        $this->createArea('drop');
    }

    /** @return Collection<int, Region> */
    #[Computed]
    public function regionStates(): Collection
    {
        return Region::query()->where('kind', 'state')->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /** @return Collection<int, Region> */
    #[Computed]
    public function pickupCities(): Collection
    {
        return $this->childRegions('city', $this->pickup_state_id);
    }

    /** @return Collection<int, Region> */
    #[Computed]
    public function pickupAreas(): Collection
    {
        return $this->childRegions('area', $this->pickup_city_id);
    }

    /** @return Collection<int, Region> */
    #[Computed]
    public function dropCities(): Collection
    {
        return $this->childRegions('city', $this->drop_state_id);
    }

    /** @return Collection<int, Region> */
    #[Computed]
    public function dropAreas(): Collection
    {
        return $this->childRegions('area', $this->drop_city_id);
    }

    /** Walk a saved address's region chain back into the cascading pickers. */
    protected function seedRegionPickers(string $leg): void
    {
        $region = $this->{$leg.'_region_id'} ? Region::with('parent.parent.parent')->find($this->{$leg.'_region_id'}) : null;

        $this->{$leg.'_state_id'} = null;
        $this->{$leg.'_city_id'} = null;

        while ($region) {
            match ($region->kind) {
                'state' => $this->{$leg.'_state_id'} = $region->id,
                'city' => $this->{$leg.'_city_id'} = $region->id,
                default => null,
            };
            $region = $region->parent;
        }
    }

    protected function seedDropRegionPickers(): void
    {
        $this->seedRegionPickers('drop');
    }

    protected function clearBelowState(string $leg): void
    {
        $this->{$leg.'_city_id'} = null;
        $this->{$leg.'_region_id'} = null;
    }

    /** A city needs its state; without one there is nothing to hang it off. */
    protected function createCity(string $leg): void
    {
        if (! $this->{$leg.'_state_id'}) {
            return;
        }

        $created = $this->quickCreate(
            modelClass: Region::class,
            targetProperty: $leg.'_city_id',
            searchProperty: $leg.'CitySearch',
            permission: 'region_master.create',
            label: 'City',
            matchOn: ['kind' => 'city', 'parent_id' => $this->{$leg.'_state_id'}],
        );

        if ($created) {
            // Mirror what picking an existing city does.
            $this->{$leg.'_region_id'} = $this->{$leg.'_city_id'};
            unset($this->{$leg === 'pickup' ? 'pickupCities' : 'dropCities'});
        }
    }

    protected function createArea(string $leg): void
    {
        if (! $this->{$leg.'_city_id'}) {
            return;
        }

        $created = $this->quickCreate(
            modelClass: Region::class,
            targetProperty: $leg.'_region_id',
            searchProperty: $leg.'AreaSearch',
            permission: 'region_master.create',
            label: 'Area',
            matchOn: ['kind' => 'area', 'parent_id' => $this->{$leg.'_city_id'}],
        );

        if ($created) {
            unset($this->{$leg === 'pickup' ? 'pickupAreas' : 'dropAreas'});
        }
    }

    /** @return Collection<int, Region> */
    protected function childRegions(string $kind, ?int $parentId): Collection
    {
        return $parentId
            ? Region::query()->where('kind', $kind)->where('parent_id', $parentId)->where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : collect();
    }
}
