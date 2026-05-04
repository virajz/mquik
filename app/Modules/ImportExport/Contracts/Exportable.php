<?php

namespace App\Modules\ImportExport\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * Implement this on a per-module exporter class to make a module exportable.
 *
 * Each module's module.php declares its exporter:
 *   'exportable' => SpareBrandExporter::class,
 *
 * The ExportButton component reads the registry and shows the Export option
 * in the dropdown only when this is declared.
 */
interface Exportable
{
    /**
     * Display label for the export action (used in toasts, file names).
     */
    public function label(): string;

    /**
     * Column headers in the order they should appear in the CSV.
     *
     * @return array<int, string> e.g. ['ID', 'Name', 'Code', 'Active', 'Notes']
     */
    public function headers(): array;

    /**
     * Eloquent query that returns ALL rows the user is allowed to export.
     * Apply any visibility / soft-delete / scope rules here.
     */
    public function query(): Builder;

    /**
     * Map a single Eloquent model to a row of CSV values.
     * Order MUST match headers().
     *
     * @return array<int, scalar|null>
     */
    public function row(object $model): array;

    /**
     * Filename suggestion (without extension).
     * The engine appends timestamp + extension.
     */
    public function fileName(): string;
}
