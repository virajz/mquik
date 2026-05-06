<?php

namespace App\Modules\InspectionTemplateMaster\Database\Seeders;

use App\Modules\InspectionItemGroupMaster\Models\InspectionItemGroupMaster;
use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class InspectionTemplateMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Real workshop checklists assembled from the seeded items.
        // We resolve groups + items by name (which the InspectionItem seeder firstOrCreate'd).

        $templates = [
            [
                'name' => 'PMS STANDARD',
                'code' => 'PMS-STD',
                'applies_to' => 'pms',
                'items' => $this->itemsByGroup('ENGINE')
                    ->merge($this->itemsByGroup('FLUID')->take(2))
                    ->values(),
            ],
            [
                'name' => 'TYRE SERVICE',
                'code' => 'TYR-SVC',
                'applies_to' => 'tyre',
                'items' => $this->itemsByGroup('TYRE'),
            ],
            [
                'name' => 'BODYSHOP ESTIMATE',
                'code' => 'BSH-EST',
                'applies_to' => 'bodyshop',
                'items' => $this->itemsByGroup('BODY'),
            ],
            [
                'name' => 'PRE-DELIVERY BASIC',
                'code' => 'PRE-DLV',
                'applies_to' => 'basic',
                // One item from each group that has any items
                'items' => InspectionItemGroupMaster::query()
                    ->orderBy('name')
                    ->get()
                    ->flatMap(fn ($group) => InspectionItemMaster::query()
                        ->where('inspection_item_group_id', $group->id)
                        ->orderBy('name')
                        ->limit(1)
                        ->get()),
            ],
        ];

        foreach ($templates as $tpl) {
            $template = InspectionTemplateMaster::firstOrCreate(
                ['name' => $tpl['name']],
                [
                    'code' => $tpl['code'],
                    'applies_to' => $tpl['applies_to'],
                    'is_active' => true,
                ],
            );

            $sync = $tpl['items']
                ->values()
                ->mapWithKeys(fn ($item, $i) => [
                    $item->id => ['position' => $i + 1, 'is_required' => true],
                ])
                ->all();

            $template->items()->sync($sync);
        }
    }

    /** Resolve all items belonging to the named group (group resolved by name, case-insensitive). */
    protected function itemsByGroup(string $groupName): Collection
    {
        $groupId = InspectionItemGroupMaster::query()
            ->whereLike('name', $groupName, caseSensitive: false)
            ->value('id');

        if (! $groupId) {
            return collect();
        }

        return InspectionItemMaster::query()
            ->where('inspection_item_group_id', $groupId)
            ->orderBy('name')
            ->get();
    }
}
