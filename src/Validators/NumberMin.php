<?php

namespace MOIREI\Metre\Validators;

use Spatie\DataTransferObject\Validation\ValidationResult;
use Spatie\DataTransferObject\Validator;

// #[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class NumberMin implements Validator
{
    public function __construct(
        private float $min,
    ) {
        //
    }

    public function validate(mixed $value): ValidationResult
    {
        if ($value < $this->min) {
            return ValidationResult::invalid("Value should be greater than or equal to {$this->min}");
        }

        return ValidationResult::valid();
    }
}
