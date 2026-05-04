<?php

namespace App\Modules\ImportExport\Support;

use League\Csv\Reader;

/**
 * Cheap, low-memory CSV preview helper.
 * Reads the header row + first N data rows for the wizard's mapping step.
 */
class CsvPreview
{
    /**
     * @return array{headers: array<int, string>, rows: array<int, array<int, string>>, total_data_rows: int}
     */
    public static function read(string $absolutePath, int $previewRows = 5): array
    {
        $reader = Reader::from($absolutePath);
        $reader->setHeaderOffset(0);

        $headers = array_values($reader->getHeader());

        $rows = [];
        $count = 0;
        $totalDataRows = 0;

        foreach ($reader->getRecords() as $record) {
            $totalDataRows++;
            if ($count < $previewRows) {
                $rows[] = array_values($record);
                $count++;
            }
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'total_data_rows' => $totalDataRows,
        ];
    }
}
