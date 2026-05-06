<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Lets a model participate in Master Search.
 *
 * Each model declares:
 *   protected static array $searchableFields = ['name', 'phone', 'email'];
 *
 * And opts in via its module manifest:
 *   // module.php
 *   'searchable' => [
 *       'model' => CustomerMaster::class,
 *       'label' => 'Customers',          // optional, defaults to module label
 *       'icon'  => 'user-circle',        // optional, defaults to module icon
 *       'route' => 'customer-master.index', // optional, defaults to module route
 *   ],
 */
trait Searchable
{
    /**
     * Postgres-safe case-insensitive search across the configured fields.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        $fields = static::searchableFields();

        return $query->where(function (Builder $q) use ($fields, $term) {
            $needle = '%'.$term.'%';
            foreach ($fields as $field) {
                $q->orWhereLike($field, $needle, caseSensitive: false);
            }
        });
    }

    /**
     * Override on the model with `protected static array $searchableFields = [...]`.
     *
     * @return array<int, string>
     */
    public static function searchableFields(): array
    {
        return property_exists(static::class, 'searchableFields')
            ? static::$searchableFields
            : ['name'];
    }

    /**
     * Override on the model to control how each row appears in search results.
     */
    public function toSearchResult(): array
    {
        return [
            'id' => $this->getKey(),
            'title' => $this->name ?? (string) $this->getKey(),
            'subtitle' => null,
        ];
    }
}
