<?php

namespace App\Modules\JobCard\Support;

/**
 * How the "Last done" list on a job card is ordered.
 *
 * Workshops disagree on this: a fast-fit bay wants whatever is overdue at the
 * top, a service centre often just wants the last visit's work. Lives in its own
 * class rather than the trait so the settings screen can read the options
 * without the trait being applied to it.
 */
class ServiceHistorySort
{
    public const DUE_FIRST = 'due_first';

    public const MOST_OVERDUE = 'most_overdue';

    public const RECENT = 'recent';

    public const FREQUENT = 'frequent';

    public const ALPHABETICAL = 'alphabetical';

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::DUE_FIRST => 'Due first, then most recent',
            self::MOST_OVERDUE => 'Most overdue first',
            self::RECENT => 'Most recent first',
            self::FREQUENT => 'Most often done first',
            self::ALPHABETICAL => 'A to Z',
        ];
    }
}
