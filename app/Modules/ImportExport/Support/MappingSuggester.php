<?php

namespace App\Modules\ImportExport\Support;

/**
 * Suggests the best CSV header for each target entity column.
 * Match strategy:
 *   1. Exact normalized match (case + spaces + underscores stripped)
 *   2. CSV header contains the target name (or vice versa)
 *   3. Word overlap > 50% (fuzzy)
 */
class MappingSuggester
{
    /**
     * @param  array<int, string>  $csvHeaders  e.g. ['Brand Name', 'Code', 'Active?']
     * @param  array<string, array{label:string}>  $targetColumns  keyed by target column name
     * @return array<string, ?string> target column => matched CSV header (or null)
     */
    public static function suggest(array $csvHeaders, array $targetColumns): array
    {
        $suggestions = [];
        $normalizedCsvHeaders = array_map(self::normalize(...), $csvHeaders);

        foreach ($targetColumns as $targetCol => $meta) {
            $candidates = [
                self::normalize($targetCol),
                self::normalize($meta['label'] ?? $targetCol),
            ];

            $match = null;

            // Strategy 1: exact match
            foreach ($candidates as $needle) {
                $idx = array_search($needle, $normalizedCsvHeaders, true);
                if ($idx !== false) {
                    $match = $csvHeaders[$idx];
                    break;
                }
            }

            // Strategy 2: substring match
            if ($match === null) {
                foreach ($candidates as $needle) {
                    foreach ($normalizedCsvHeaders as $idx => $haystack) {
                        if ($haystack !== '' && (str_contains($haystack, $needle) || str_contains($needle, $haystack))) {
                            $match = $csvHeaders[$idx];
                            break 2;
                        }
                    }
                }
            }

            // Strategy 3: word overlap > 50%
            if ($match === null) {
                $bestScore = 0.5;
                foreach ($normalizedCsvHeaders as $idx => $haystack) {
                    foreach ($candidates as $needle) {
                        $score = self::wordOverlap($needle, $haystack);
                        if ($score > $bestScore) {
                            $bestScore = $score;
                            $match = $csvHeaders[$idx];
                        }
                    }
                }
            }

            $suggestions[$targetCol] = $match;
        }

        return $suggestions;
    }

    protected static function normalize(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9 ]/', ' ', $value) ?? '';
        $value = preg_replace('/[_\-\s]+/', ' ', $value) ?? '';

        return trim($value);
    }

    protected static function wordOverlap(string $a, string $b): float
    {
        $aWords = array_filter(explode(' ', $a));
        $bWords = array_filter(explode(' ', $b));

        if (empty($aWords) || empty($bWords)) {
            return 0;
        }

        $intersection = count(array_intersect($aWords, $bWords));
        $union = count(array_unique([...$aWords, ...$bWords]));

        return $union === 0 ? 0 : $intersection / $union;
    }
}
