<?php

namespace App\Support;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;

/**
 * Who is standing at this screen.
 *
 * Employees have no user accounts in this application, so screens that are
 * personal — the technician bench, the notification bell — have no way to know
 * whose work to show. Rather than each screen asking again, the choice is made
 * once and kept in the session.
 *
 * When employees do get logins, this is the single place that has to change.
 */
class ActingEmployee
{
    public const SESSION_KEY = 'acting_employee_id';

    public static function id(): ?int
    {
        $id = session(self::SESSION_KEY);

        return $id ? (int) $id : null;
    }

    public static function set(?int $employeeId): void
    {
        if ($employeeId) {
            session([self::SESSION_KEY => $employeeId]);

            return;
        }

        session()->forget(self::SESSION_KEY);
    }

    public static function get(): ?EmployeeMaster
    {
        $id = self::id();

        return $id ? EmployeeMaster::find($id) : null;
    }

    public static function name(): ?string
    {
        return self::get()?->name;
    }
}
