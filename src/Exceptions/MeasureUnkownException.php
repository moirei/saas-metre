<?php

namespace MOIREI\Metre\Exceptions;

class MeasureUnkownException extends \Exception
{
    public function __construct($name)
    {
        parent::__construct("Measure $name doesn't exist.");
    }
}
