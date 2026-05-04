<?php

namespace App\Modules\ImportExport\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExportCompleted implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public int $exportId,
        public int $userId,
        public string $fileName,
        public int $totalRows,
        public string $downloadUrl,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('exports.'.$this->userId);
    }

    public function broadcastAs(): string
    {
        return 'completed';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->exportId,
            'status' => 'completed',
            'file_name' => $this->fileName,
            'total_rows' => $this->totalRows,
            'download_url' => $this->downloadUrl,
        ];
    }
}
