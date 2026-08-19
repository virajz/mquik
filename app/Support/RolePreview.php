<?php

namespace App\Support;

use Spatie\Permission\Models\Role;

/**
 * Lets an admin look at the app as a given job sees it.
 *
 * An admin holds every permission, so their own screens can never show what a
 * service advisor or store manager actually gets. This keeps a chosen role in
 * the session and the alert banner reads it — a preview of someone else's
 * inbox, not a change of the admin's own rights.
 */
class RolePreview
{
    public const SESSION_KEY = 'preview_role';

    /** Roles worth previewing — the job roles, not the god account. */
    public const EXCLUDED = ['Super Admin'];

    public static function role(): ?string
    {
        $role = session(self::SESSION_KEY);

        return is_string($role) && $role !== '' ? $role : null;
    }

    public static function set(?string $role): void
    {
        if ($role) {
            session([self::SESSION_KEY => $role]);

            return;
        }

        session()->forget(self::SESSION_KEY);
    }

    public static function isPreviewing(): bool
    {
        return self::role() !== null;
    }

    /** Only an admin may look through someone else's eyes. */
    public static function isAvailable(): bool
    {
        if (! config('mquik.features.role_preview', true)) {
            return false;
        }

        return (bool) auth()->user()?->hasRole('Super Admin');
    }

    /** The signed-in user's own first role, used when not previewing. */
    public static function currentUserRole(): ?string
    {
        return auth()->user()?->getRoleNames()->first();
    }

    /** @return list<string> */
    public static function selectableRoles(): array
    {
        return Role::query()
            ->whereNotIn('name', self::EXCLUDED)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }
}
