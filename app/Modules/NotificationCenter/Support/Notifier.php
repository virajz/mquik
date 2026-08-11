<?php

namespace App\Modules\NotificationCenter\Support;

use App\Modules\NotificationCenter\Models\AppNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * The one way to raise an in-app alert.
 *
 * Every call site addresses employees, never users — see the migration note.
 * Null recipients are skipped rather than erroring: a job card without an
 * assigned advisor should not break the save that triggered the alert.
 */
class Notifier
{
    /**
     * @param  array<string, mixed>  $attrs  title, body, url, subject
     */
    public static function toEmployee(?int $employeeId, string $type, array $attrs): ?AppNotification
    {
        if (! $employeeId) {
            return null;
        }

        $subject = $attrs['subject'] ?? null;

        return AppNotification::create([
            'employee_id' => $employeeId,
            'type' => $type,
            'title' => $attrs['title'] ?? '',
            'body' => $attrs['body'] ?? null,
            'url' => $attrs['url'] ?? null,
            'requires_action' => (bool) ($attrs['requires_action'] ?? false),
            'severity' => $attrs['severity'] ?? AppNotification::SEVERITY_INFO,
            'action_label' => $attrs['action_label'] ?? null,
            'subject_type' => $subject instanceof Model ? $subject::class : null,
            'subject_id' => $subject instanceof Model ? $subject->getKey() : null,
            'actor_user_id' => auth()->id(),
        ]);
    }

    /**
     * Raise an alert against a job rather than a person — "whoever is on the
     * store desk". Used for the standing alerts that sit on the banner.
     *
     * @param  array<string, mixed>  $attrs
     */
    public static function toRole(string $role, string $type, array $attrs): AppNotification
    {
        $subject = $attrs['subject'] ?? null;

        return AppNotification::create([
            'role' => $role,
            'type' => $type,
            'title' => $attrs['title'] ?? '',
            'body' => $attrs['body'] ?? null,
            'url' => $attrs['url'] ?? null,
            'requires_action' => (bool) ($attrs['requires_action'] ?? false),
            'severity' => $attrs['severity'] ?? AppNotification::SEVERITY_INFO,
            'action_label' => $attrs['action_label'] ?? null,
            'subject_type' => $subject instanceof Model ? $subject::class : null,
            'subject_id' => $subject instanceof Model ? $subject->getKey() : null,
            'actor_user_id' => auth()->id(),
        ]);
    }

    /**
     * Fan the same alert out to several people — the advisor *and* the store
     * in-charge, typically. Duplicates and nulls are dropped.
     *
     * @param  array<int, ?int>  $employeeIds
     * @param  array<string, mixed>  $attrs
     * @return Collection<int, AppNotification>
     */
    public static function toEmployees(array $employeeIds, string $type, array $attrs): Collection
    {
        return collect($employeeIds)
            ->filter()
            ->unique()
            ->map(fn (int $id) => self::toEmployee($id, $type, $attrs))
            ->filter()
            ->values();
    }
}
