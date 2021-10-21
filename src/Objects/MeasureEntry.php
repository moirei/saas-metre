<?php

namespace MOIREI\Metre\Objects;

use MOIREI\Metre\Validators\NumberBetween;
use MOIREI\Metre\Validators\NumberMin;
use Spatie\DataTransferObject\DataTransferObject;

final class MeasureEntry extends DataTransferObject
{
    /**
     * Entry timestamp.
     *
     * @var int
     */
    #[NumberMin(0)]
    public int $ts;

    /**
     * Entry count.
     *
     * @var int
     */
    #[NumberBetween(1, 1000)]
    public int $count;

    /**
     * Entry tags.
     *
     * @var string[]
     */
    public array $tags = [];
}
