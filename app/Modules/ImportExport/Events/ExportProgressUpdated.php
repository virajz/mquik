<?php

namespace App\Modules\ImportExport\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExportProgressUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Pass primitive snapshots — DO NOT pass the Export model itself, because
     * SerializesModels re-fetches by ID when the broadcast is processed.
     * Even with ShouldBroadcastNow that's fine, but staying primitive avoids
     * any future regression where someone re-enables queued broadcasts.
     */
    public function __construct(
        public int $exportId,
        public int $userId,
        public string $status,
        public int $processedRows,
        public int $totalRows,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('exports.'.$this->userId);
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
            'id' => $this->exportId,
            'status' => $this->status,
            'processed_rows' => $this->processedRows,
            'total_rows' => $this->totalRows,
            'percent' => $percent,
        ];
    }
}
