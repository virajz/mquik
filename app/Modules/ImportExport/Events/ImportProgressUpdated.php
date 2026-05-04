<?php

namespace App\Modules\ImportExport\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImportProgressUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public int $importId,
        public int $userId,
        public string $status,
        public bool $dryRun,
        public int $processedRows,
        public int $totalRows,
        public int $createdCount,
        public int $updatedCount,
        public int $skippedCount,
        public int $errorCount,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('imports.'.$this->userId);
    }

    public function broadcastAs(): string
    {
        return 'progress';
    }

    public function broadcastWith(): array
    {
        $percent = $this->totalRows === 0
            ? 0
            : (int) min(100, round(($this->processedRows / $this->totalRows) * 100));

        return [
            'id' => $this->importId,
            'status' => $this->status,
            'dry_run' => $this->dryRun,
            'processed_rows' => $this->processedRows,
            'total_rows' => $this->totalRows,
            'created_count' => $this->createdCount,
            'updated_count' => $this->updatedCount,
            'skipped_count' => $this->skippedCount,
            'error_count' => $this->errorCount,
            'percent' => $percent,
        ];
    }
}
