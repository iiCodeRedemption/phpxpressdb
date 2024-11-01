<?php

class Utils
{
    public static function getTypedValue(mixed $value): mixed
    {
        return match (true) {
            is_int($value), is_float($value) => $value,
            is_numeric($value) => $value + 0,
            is_null($value) => 'NULL',
            default => "'$value'",
        };
    }
}
