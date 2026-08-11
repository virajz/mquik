<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Create a login for a role, so each job can be seen from its own side.
 */
class MakeUserCommand extends Command
{
    protected $signature = 'mquik:make-user
        {--name= : Display name}
        {--email= : Login email}
        {--password=password : Password}
        {--role= : Role to assign}
        {--employee= : Employee id this login belongs to}';

    protected $description = 'Create (or update) a user and assign a role.';

    public function handle(): int
    {
        $email = $this->option('email') ?: $this->ask('Email');
        $role = $this->option('role') ?: $this->choice('Role', Role::pluck('name')->all());

        if (! Role::where('name', $role)->exists()) {
            $this->error("Role [{$role}] does not exist. Run: php artisan mquik:setup-roles");

            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => $email]);
        $user->name = $this->option('name') ?: ($user->name ?: str($email)->before('@')->headline());
        $user->password = Hash::make($this->option('password'));
        $user->email_verified_at ??= now();
        $user->save();

        $user->syncRoles([$role]);

        $line = "{$user->name} <{$user->email}> → {$role}";

        if ($employeeId = $this->option('employee')) {
            $employee = EmployeeMaster::find($employeeId);
            $line .= $employee ? "  (employee: {$employee->name})" : '  (employee not found)';
        }

        $this->info($line);
        $this->line('Password: '.$this->option('password'));

        return self::SUCCESS;
    }
}
