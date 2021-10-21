<?php

namespace MOIREI\Metre\Objects;

use MOIREI\Metre\Validators\NumberMin;
use Spatie\DataTransferObject\DataTransferObject;
use Spatie\DataTransferObject\Attributes\CastWith;
use Spatie\DataTransferObject\Casters\ArrayCaster;

final class Measure extends DataTransferObject
{
    /**
     * The name/key of the measure.
     * E.g. "user_accounts".
     *
     * @var string
     */
    public string $name;

    /**
     * Details of the browsing client, including software and operating versions.
     *
     * @var \MOIREI\Metre\Objects\MeasureType
     */
    public MeasureType $type;

    /**
     * The usage limit of the measure.
     *
     * @var float
     */
    // #[NumberMin(0)]
    public ?float $limit;

    /**
     * Default tags for an entry.
     *
     * @var string[]
     */
    public array $defaultTags = [];

    /**
     * Measure entries.
     *
     * @var \MOIREI\Metre\Objects\MeasureEntry[]
     */
    #[CastWith(ArrayCaster::class, MeasureEntry::class)]
    public array $entries = [];
}
