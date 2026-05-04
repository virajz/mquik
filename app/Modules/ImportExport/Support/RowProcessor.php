<?php

namespace App\Modules\ImportExport\Support;

use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\ImportExport\Models\Import;
use RuntimeException;

/**
 * Owns per-row decision logic — mapping, validation, dedup, dispatch.
 * Pulled out of ProcessImportJob to keep the job under 200 lines and to make
 * row decisions independently testable.
 */
class RowProcessor
{
    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public int $errors = 0;

    public function __construct(
        protected Import $import,
        protected Importable $importer,
        protected string $modelClass,
    ) {}

    /**
     * Process one CSV record. Returns validation errors if any (the caller decides
     * whether to log + skip or halt). Returns empty array on success.
     *
     * @return array<int, string>
     */
    public function process(array $csvRow): array
    {
        $mapped = $this->mapRow($csvRow);
        $errors = $this->importer->validateRow($mapped);

        if (! empty($errors)) {
            $this->errors++;

            return $errors;
        }

        $this->dispatch($mapped);

        return [];
    }

    /** Map a CSV record (assoc by header) to target columns using $import->mapping */
    protected function mapRow(array $csvRow): array
    {
        $mapped = [];

        foreach ($this->import->mapping ?? [] as $targetCol => $csvHeader) {
            if (! $csvHeader) {
                continue;
            }
            $value = $csvRow[$csvHeader] ?? null;
            $mapped[$targetCol] = $this->coerce($value);
        }

        return $mapped;
    }

    /** Convert empty strings, YES/NO, true/false, 1/0 to PHP types */
    protected function coerce(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }
        if ($value === '') {
            return null;
        }

        return match (strtolower($value)) {
            'yes', 'true', '1' => true,
            'no', 'false', '0' => false,
            default => $value,
        };
    }

    protected function dispatch(array $data): void
    {
        $existing = $this->findExisting($data);
        $behavior = $this->import->behavior['duplicates'] ?? 'upsert';

        if ($existing) {
            match ($behavior) {
                'upsert' => $this->doUpdate($existing, $data),
                'create_only', 'skip' => $this->skipped++,
                'error' => throw new RuntimeException('Duplicate row matching: '.json_encode($this->matchKeys($data))),
                default => $this->skipped++,
            };
        } else {
            $this->doCreate($data);
        }
    }

    protected function findExisting(array $data): ?object
    {
        $query = ($this->modelClass)::query();

        foreach ($this->importer->uniqueBy() as $col) {
            if (! array_key_exists($col, $data) || $data[$col] === null) {
                return null;
            }
            $query->where($col, $data[$col]);
        }

        return $query->first();
    }

    protected function matchKeys(array $data): array
    {
        return array_intersect_key($data, array_flip($this->importer->uniqueBy()));
    }

    protected function doCreate(array $data): void
    {
        if (! $this->import->dry_run) {
            $this->importer->createRecord($data);
        }
        $this->created++;
    }

    protected function doUpdate(object $existing, array $data): void
    {
        if (! $this->import->dry_run) {
            $this->importer->updateRecord($existing, $data);
        }
        $this->updated++;
    }
}
