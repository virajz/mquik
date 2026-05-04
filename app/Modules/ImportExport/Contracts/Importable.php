<?php

namespace App\Modules\ImportExport\Contracts;

/**
 * Implement this on a per-module importer class to make a module importable.
 *
 * Each module's module.php declares its importer:
 *   'importable' => SpareBrandImporter::class,
 *
 * The ImportWizard reads the registry and exposes the upload → map → execute flow.
 */
interface Importable
{
    /**
     * Display label for the import action.
     */
    public function label(): string;

    /**
     * Column definitions that the wizard maps the CSV headers to.
     *
     * @return array<string, array{label:string, required:bool, type:string, default?:mixed, help?:string}>
     *                                                                                                      Key = target Eloquent column. Type = string|integer|boolean|decimal|date.
     */
    public function columns(): array;

    /**
     * Columns used to detect duplicates during import (for upsert / skip / error behavior).
     *
     * @return array<int, string> e.g. ['name'] or ['gstin']
     */
    public function uniqueBy(): array;

    /**
     * Validate a single mapped row (associative array of target_column => value).
     * Return an array of error messages (empty = valid).
     *
     * @return array<int, string>
     */
    public function validateRow(array $data): array;

    /**
     * Persist a new record. Receives validated, type-cast data.
     */
    public function createRecord(array $data): void;

    /**
     * Update an existing record matched by uniqueBy() columns.
     */
    public function updateRecord(object $existing, array $data): void;
}
