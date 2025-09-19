<?php

namespace App\Utility;

class TypeHelper
{
    /**
     * @template T
     * @param class-string<T> $className
     * @param mixed $value
     * @return T
     */
    public static function checkMatchesClass(string $className, mixed $value)
    {
        if (!$value instanceof $className) {
            throw new \InvalidArgumentException("Value passed is not an instance of {$className}");
        }

        return $value;
    }

    /**
     * @template T
     * @param class-string<T> $className
     * @param mixed $value
     * @return T|null
     */
    public static function checkMatchesClassOrNull(string $className, mixed $value)
    {
        if ($value !== null && !$value instanceof $className) {
            throw new \InvalidArgumentException("Value passed is neither null, nor an instance of {$className}");
        }

        return $value;
    }
}
