<?php

namespace MOIREI\Metre\Objects;

use MOIREI\Metre\Traits\StringEnumTrait;
use Funeralzone\ValueObjects\ValueObject;

/**
 * App type
 *
 * @method static self METERED()
 * @method static self VOLUME()
 */
final class MeasureType implements ValueObject
{
    use StringEnumTrait;

    /**
     * Type: METERED
     *
     * @var string
     */
    public const METERED = 'metered';

    /**
     * Type: VOLUME
     *
     * @var string
     */
    public const VOLUME = 'volume';
}
