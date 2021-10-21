<?php

namespace MOIREI\Metre\Objects;

use Spatie\DataTransferObject\DataTransferObject;

final class Usage extends DataTransferObject
{
    /**
     * Usage count.
     *
     * @var int
     */
    public int $count;

    /**
     * Usage limit.
     *
     * @var float
     */
    public ?float $limit;

    /**
     * Usage entries.
     *
     * @var int
     */
    public int $entries;

    /**
     * Get usage percentage.
     *
     * @return float|null
     */
    public function percentage(): float|null
    {
        if (!$this->limit) {
            return null;
        }
        return $this->count / $this->limit;
    }
}
