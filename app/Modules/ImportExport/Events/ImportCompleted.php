<?php

namespace App\Modules\ImportExport\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImportCompleted implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public int $importId,
        public int $userId,
        public bool $dryRun,
        public int $totalRows,
        public int $createdCount,
        public int $updatedCount,
        public int $skippedCount,
        public int $errorCount,
        public ?string $errorFileUrl,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('imports.'.$this->userId);
    }

    public function broadcastAs(): string
    {
        return 'completed';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->importId,
            'status' => 'completed',
            'dry_run' => $this->dryRun,
            'total_rows' => $this->totalRows,
            'created_count' => $this->createdCount,
            'updated_count' => $this->updatedCount,
            'skipped_count' => $this->skippedCount,
            'error_count' => $this->errorCount,
            'error_file_url' => $this->errorFileUrl,
        ];
    }
}
