<?php

namespace Swis\Agents\Helpers;

/**
 * Environment helper functions for accessing environment variables
 */
class EnvHelper
{
    /**
     * Gets the value of an environment variable or returns the default value
     *
     * @param string $key The environment variable name
     * @param mixed $default The default value to return if the variable doesn't exist
     * @return mixed The environment variable value or the default
     */
    public static function get(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? false;

        if ($value === false) {
            return $default;
        }

        if (! is_string($value)) {
            return $value;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'null':
            case '(null)':
                return null;
            case 'empty':
            case '(empty)':
                return '';
        }

        return $value;
    }
}
