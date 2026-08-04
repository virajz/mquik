<?php

namespace App\Modules\Inventory\Exceptions;

use RuntimeException;

/**
 * Thrown when an issue asks for more than the ledger holds and the caller has
 * not opted into going negative.
 */
class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public readonly int $spareId,
        public readonly string $spareName,
        public readonly float $requested,
        public readonly float $available,
    ) {
        parent::__construct(sprintf(
            '%s: asked for %s but only %s in stock.',
            $spareName,
            rtrim(rtrim(number_format($requested, 2), '0'), '.'),
            rtrim(rtrim(number_format($available, 2), '0'), '.'),
        ));
    }
}
