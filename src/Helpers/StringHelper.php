<?php

namespace Swis\Agents\Helpers;

/**
 * Helper functions for manipulating string variables
 */
class StringHelper
{
    /**
     * Convert a string to snake_case.
     *
     * @param string $value
     * @return string
     */
    public static function toSnakeCase(string $value): string
    {
        if (! ctype_lower($value)) {
            $value = (string) preg_replace('/\\s+/u', '', ucwords($value));
            $value = strtolower((string) preg_replace('/(.)(?=[A-Z])/u', '$1_', $value));
        }

        return $value;
    }

    /**
     * Remove the first occurrence of $needle from $value if $needle is the first part of $value.
     *
     * @param string $value
     * @param string $needle
     * @return string
     */
    public static function removeFromStart(string $value, string $needle): string
    {
        if (str_starts_with($value, $needle)) {
            $value = substr($value, strlen($needle));
        }

        return $value;
    }

    /**
     * Remove the last occurrence of $needle from $value if $needle is the last part of $value.
     *
     * @param string $value
     * @param string $needle
     * @return string
     */
    public static function removeFromEnd(string $value, string $needle): string
    {
        if (str_ends_with($value, $needle)) {
            $value = substr($value, 0, -strlen($needle));
        }

        return $value;
    }
}
