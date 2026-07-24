<?php

namespace App\Modules\InspectionTemplateMaster\Database\Seeders;

use App\Modules\InspectionItemGroupMaster\Models\InspectionItemGroupMaster;
use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use Illuminate\Database\Seeder;

/**
 * The MQUIK 360° Vehicle Inspection Checklist, expressed as the workshop's own
 * inspection items + a template, so it renders through the existing VIO
 * checklist engine rather than a bespoke form.
 *
 * Customer complaints from the paper form are intentionally omitted here — they
 * are captured as complaint types on the job card, not as inspection items.
 */
class Mquik360TemplateSeeder extends Seeder
{
    /**
     * [group, item, check_type]. Groups and items are firstOrCreate'd, so this
     * layers onto the existing 107 checkpoints without duplicating them.
     *
     * @var array<int, array{0:string, 1:string, 2:string}>
     */
    private const ITEMS = [
        // Exterior & Safety walk-around
        ['SAFETY', 'HEADLAMPS (LOW / HIGH)', 'yes_no'],
        ['SAFETY', 'FOG LAMPS', 'yes_no'],
        ['SAFETY', 'INDICATORS / HAZARD', 'yes_no'],
        ['SAFETY', 'TAIL / BRAKE LAMPS', 'yes_no'],
        ['SAFETY', 'WIPERS & WASHER', 'yes_no'],
        ['SAFETY', 'WINDSHIELD / GLASS CRACKS', 'visual'],
        ['SAFETY', 'ORVMS / MIRRORS', 'visual'],
        ['SAFETY', 'HORN', 'yes_no'],
        ['SAFETY', 'WHEEL NUTS / BOLTS', 'visual'],

        // Tyre & Suspension
        ['TYRE', 'TYRE CONDITION (ALL 5)', 'visual'],
        ['TYRE', 'WHEEL ALIGNMENT REQUIRED', 'yes_no'],
        ['TYRE', 'WHEEL BALANCING REQUIRED', 'yes_no'],
        ['SUSPENSION', 'SHOCK ABSORBER LEAKAGE', 'visual'],
        ['SUSPENSION', 'LOWER ARM / BUSH NOISE', 'visual'],
        ['SUSPENSION', 'BALL JOINT PLAY', 'visual'],
        ['SUSPENSION', 'STABILIZER LINK NOISE', 'visual'],

        // Braking system
        ['BRAKE', 'FRONT BRAKE PADS', 'measurement'],
        ['BRAKE', 'REAR BRAKE PADS / SHOES', 'measurement'],
        ['BRAKE', 'DISC CONDITION', 'visual'],
        ['BRAKE', 'BRAKE FLUID LEVEL / COLOUR', 'visual'],
        ['BRAKE', 'BRAKE NOISE', 'visual'],
        ['BRAKE', 'HANDBRAKE PERFORMANCE', 'visual'],

        // Engine bay
        ['ENGINE', 'POWER STEERING FLUID', 'visual'],
        ['ENGINE', 'BATTERY HEALTH / TERMINALS', 'visual'],
        ['ENGINE', 'BELTS (CRACKS / NOISE)', 'visual'],
        ['ENGINE', 'HOSES / LEAKAGE', 'visual'],
        ['ENGINE', 'AIR FILTER', 'visual'],
        ['ENGINE', 'AC BELT / COMPRESSOR NOISE', 'visual'],
        ['ENGINE', 'ENGINE MOUNTING', 'visual'],
        ['ENGINE', 'TRANSMISSION MOUNTING', 'visual'],

        // Underbody (on the lift)
        ['UNDERBODY', 'ENGINE OIL LEAKAGE', 'visual'],
        ['UNDERBODY', 'GEARBOX LEAKAGE', 'visual'],
        ['UNDERBODY', 'EXHAUST NOISE / RUST', 'visual'],
        ['UNDERBODY', 'DRIVE SHAFT / CV BOOT', 'visual'],
        ['UNDERBODY', 'UNDERBODY DAMAGE', 'visual'],
        ['UNDERBODY', 'FUEL LINE / BRAKE LINE CONDITION', 'visual'],

        // Electrical & diagnostics
        ['ELECTRICAL', 'WARNING LIGHTS ON DASHBOARD', 'yes_no'],
        ['ELECTRICAL', 'SCAN TOOL USED', 'yes_no'],
        ['ELECTRICAL', 'ERROR CODES FOUND', 'yes_no'],
        ['ELECTRICAL', 'BATTERY CHARGING VOLTAGE', 'measurement'],
        ['ELECTRICAL', 'ALTERNATOR PERFORMANCE', 'visual'],

        // AC & comfort
        ['AC-HEATER', 'COOLING PERFORMANCE', 'visual'],
        ['AC-HEATER', 'BLOWER NOISE', 'visual'],
        ['AC-HEATER', 'AC SMELL', 'visual'],
        ['AC-HEATER', 'AC GAS PRESSURE', 'measurement'],
        ['AC-HEATER', 'CABIN FILTER CONDITION', 'visual'],

        // Interior
        ['INTERIOR', 'SEAT BELTS', 'yes_no'],
        ['INTERIOR', 'POWER WINDOWS', 'yes_no'],
        ['INTERIOR', 'CENTRAL LOCKING', 'yes_no'],
        ['INTERIOR', 'MUSIC SYSTEM / SCREEN', 'yes_no'],
        ['INTERIOR', 'INTERIOR CLEANLINESS', 'visual'],

        // Exterior finish
        ['BODY', 'PAINT CONDITION', 'visual'],
        ['BODY', 'WIPER BLADES', 'visual'],
    ];

    public function run(): void
    {
        $units = ['measurement' => 'mm', 'visual' => null, 'yes_no' => null];

        $itemIds = [];
        foreach (self::ITEMS as [$groupName, $itemName, $checkType]) {
            $group = InspectionItemGroupMaster::firstOrCreate(['name' => $groupName], ['is_active' => true]);

            $item = InspectionItemMaster::firstOrCreate(
                ['inspection_item_group_id' => $group->id, 'name' => $itemName],
                ['check_type' => $checkType, 'measurement_unit' => $units[$checkType], 'is_active' => true],
            );

            $itemIds[] = $item->id;
        }

        $template = InspectionTemplateMaster::firstOrCreate(
            ['name' => 'MQUIK 360'],
            ['code' => 'MQ-360', 'applies_to' => 'pms', 'is_active' => true],
        );

        // Position preserves the walk-around order of the paper checklist.
        $template->items()->syncWithoutDetaching(
            collect($itemIds)->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i + 1]])->all(),
        );
    }
}
