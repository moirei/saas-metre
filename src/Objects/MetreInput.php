<?php

namespace MOIREI\Metre\Objects;

use Spatie\DataTransferObject\DataTransferObject;
use Spatie\DataTransferObject\Attributes\CastWith;
use Spatie\DataTransferObject\Casters\ArrayCaster;

final class MetreInput extends DataTransferObject
{
    /**
     * The start time for entries periods (unix time).
     *
     * @var int
     */
    public int $startOfPeriods;

    /**
     * Periods entries (unix time).
     *
     * @var int[]
     */
    public array $periods = [];

    /**
     * Metre measures.
     *
     * @var \MOIREI\Metre\Objects\Measure[]
     */
    #[CastWith(ArrayCaster::class, Measure::class)]
    public array $measures = [];
}
