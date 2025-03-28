<?php

namespace Swis\Agents\Helpers;

/**
 * Helper functions for working with array variables
 */
class ArrayHelper
{
    /**
     * Get the first item from the array passing the given truth test.
     *
     * @template Tvalue of mixed
     * @template Tdefault of mixed|null
     * @param array<Tvalue> $items
     * @param callable $search
     * @param Tdefault $default
     * @return Tvalue|Tdefault
     */
    public static function first(array $items, callable $search, mixed $default = null): mixed
    {
        foreach ($items as $item) {
            if ($search($item)) {
                return $item;
            }
        }

        return $default;
    }

    /**
     * Returns true if the array contains an item passing the given truth test.
     *
     * @param array<mixed> $items
     * @param callable $search
     * @return bool
     */
    public static function contains(array $items, callable $search): bool
    {
        return self::first($items, $search) !== null;
    }
}
