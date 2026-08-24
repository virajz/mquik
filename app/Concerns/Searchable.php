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
     *
     * Multi-token: the term is split on whitespace; each token must match SOMEWHERE
     * across the searchable fields (OR), and ALL tokens must match (AND).
     *
     * Fuzzy (Postgres only): for tokens of 4+ characters, also accepts trigram-similarity
     * matches (`pg_trgm`). Catches typos like "viraj" ≈ "viaraj". GIN trigram indexes per
     * column are created by the `enable_pg_trgm_and_create_search_indexes` migration.
     *
     * Examples:
     *   "ra ri"       → "Viraj Zaveri" matches (both tokens land in `name`)
     *   "viraj 7874"  → matches a customer with name "Viraj …" AND phone "…7874…"
     *   "viaraj"      → matches "Viraj" on Postgres via similarity > 0.4
     *   "viraj xxxx"  → does NOT match if "xxxx" appears in no field
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        // `%` separates too, so a pasted "RAJESH%KA01" works like "RAJESH KA01".
        // People type it expecting a wildcard; treating it as a separator gets
        // them the result they wanted either way.
        $tokens = preg_split('/[\s%]+/', $term, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($tokens)) {
            return $query;
        }

        $fields = static::searchableFields();
        $isPostgres = $query->getConnection()->getDriverName() === 'pgsql';

        return $query->where(function (Builder $outer) use ($fields, $tokens, $isPostgres) {
            foreach ($tokens as $token) {
                $needle = '%'.$token.'%';
                $outer->where(function (Builder $sub) use ($fields, $needle, $token, $isPostgres) {
                    foreach ($fields as $field) {
                        // "relation.column" searches through a relationship, so a job
                        // card can still be found by customer name or registration no.
                        if (str_contains($field, '.')) {
                            $relation = substr($field, 0, strrpos($field, '.'));
                            $column = substr($field, strrpos($field, '.') + 1);
                            $sub->orWhereHas($relation, function (Builder $rel) use ($column, $needle, $token, $isPostgres) {
                                $rel->where(function (Builder $inner) use ($column, $needle, $token, $isPostgres) {
                                    self::applyFieldMatch($inner, $column, $needle, $token, $isPostgres);
                                });
                            });

                            continue;
                        }

                        self::applyFieldMatch($sub, $field, $needle, $token, $isPostgres);
                    }
                });
            }
        });
    }

    /**
     * One field's contribution to a token match: a case-insensitive LIKE, plus a
     * trigram-similarity fallback on Postgres.
     *
     * Fuzzy fallback: `word_similarity` scans for the best matching word/substring
     * within the field value rather than comparing whole strings. Min 4 chars on the
     * token keeps short inputs from over-matching; threshold 0.4 catches typos
     * ("viaraj"→"VIRAJ", "zveri"→"ZAVERI") without returning unrelated rows.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     */
    protected static function applyFieldMatch(Builder $query, string $field, string $needle, string $token, bool $isPostgres): void
    {
        $query->orWhereLike($field, $needle, caseSensitive: false);

        if ($isPostgres && self::isFuzzyCandidate($token) && self::isSafeIdentifier($field)) {
            $query->orWhereRaw(sprintf('word_similarity(?, "%s"::text) > 0.4', $field), [$token]);
        }
    }

    /**
     * Whether a token should get the trigram treatment.
     *
     * Only alphabetic words qualify. Structured identifiers — job card numbers,
     * codes, phone numbers — are typed exactly and share long common prefixes, so
     * trigram matching on them returns thousands of near-identical rows instead of
     * the one the user wanted. Typo tolerance is for names, not for `MQ/JC/21-22/3099`.
     */
    protected static function isFuzzyCandidate(string $token): bool
    {
        return mb_strlen($token) >= 4 && preg_match('/^\p{L}+$/u', $token) === 1;
    }

    /** Defensive: only allow simple identifier names through `whereRaw`. */
    protected static function isSafeIdentifier(string $field): bool
    {
        return (bool) preg_match('/^[a-z][a-z0-9_]*$/i', $field);
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
