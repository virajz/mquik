<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Save one child row of a repeater form — update it when the form carried an
 * id, create it when it did not.
 *
 * Replaces `$parent->items()->updateOrCreate(['id' => $row['id'] ?? null], $payload)`.
 * That looks right but is not: `updateOrCreate` runs `firstOrNew($attributes)`,
 * so a null id is *set on the model* and lands in the INSERT column list as
 * `("id", …) values (NULL, …)`. SQLite treats an explicit null primary key as
 * "auto-assign", so the whole test suite passes; Postgres rejects it with a
 * not-null violation and every new child row fails in production.
 */
class ChildRows
{
    /**
     * @param  HasMany<Model, Model>|Relation<Model, Model, mixed>  $relation
     * @param  array<string, mixed>  $payload
     */
    public static function upsert(Relation $relation, mixed $id, array $payload): Model
    {
        $existing = $id ? (clone $relation)->getQuery()->whereKey($id)->first() : null;

        if ($existing) {
            $existing->update($payload);

            return $existing;
        }

        return $relation->create($payload);
    }
}
