<?php

namespace MOIREI\Metre\Exceptions;

class MeasureExhaustedException extends \Exception
{
    public function __construct()
    {
        parent::__construct("Measure usage has been exhausted.");
    }
}
