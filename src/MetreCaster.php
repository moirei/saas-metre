<?php

namespace MOIREI\Metre;

use MOIREI\Metre\Metre;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class MetreCaster implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return \MOIREI\Metre\Metre
     */
    public function get($model, $key, $value, $attributes)
    {
        return new Metre(
            json_decode($value, true) ?? [],
        );
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  \MOIREI\Metre\Metre|array  $value
     * @param  array  $attributes
     * @return array
     */
    public function set($model, $key, $value, $attributes)
    {
        if ($value instanceof Metre) {
            $value = $value->toArray();
        } elseif (!is_array($value)) {
            $value = [];
        }
        return [$key => json_encode($value)];
    }
}
