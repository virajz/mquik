<?php

namespace App\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn (Model $m) => static::recordAudit($m, 'created', null, $m->getAttributes()));

        static::updated(function (Model $m) {
            $changes = $m->getChanges();
            $original = array_intersect_key($m->getOriginal(), $changes);
            // Only record if meaningful changes exist beyond timestamps.
            $skip = ['updated_at'];
            $meaningful = array_diff_key($changes, array_flip($skip));
            if (empty($meaningful)) {
                return;
            }
            static::recordAudit($m, 'updated', $original, $changes);
        });

        static::deleted(fn (Model $m) => static::recordAudit($m, 'deleted', $m->getOriginal(), null));

        if (method_exists(static::class, 'restored')) {
            static::restored(fn (Model $m) => static::recordAudit($m, 'restored', null, $m->getAttributes()));
        }
    }

    protected static function recordAudit(Model $model, string $event, ?array $old, ?array $new): void
    {
        $user = auth()->user();
        $request = request();

        AuditLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'event' => $event,
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
            'model_label' => method_exists($model, 'auditLabel')
                ? $model->auditLabel()
                : ($model->name ?? (string) $model->getKey()),
            'old_values' => $old ? static::sanitizeForAudit($old) : null,
            'new_values' => $new ? static::sanitizeForAudit($new) : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent() ? substr($request->userAgent(), 0, 1024) : null,
        ]);
    }

    /**
     * Redact obvious secrets / large blobs.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected static function sanitizeForAudit(array $values): array
    {
        $redact = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];
        foreach ($redact as $key) {
            if (array_key_exists($key, $values)) {
                $values[$key] = '***';
            }
        }

        return $values;
    }
}
