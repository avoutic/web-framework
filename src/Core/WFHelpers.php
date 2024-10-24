<?php

namespace WebFramework\Core;

/**
 * Class WFHelpers.
 *
 * Provides various helper functions for the WebFramework.
 */
class WFHelpers
{
    /**
     * Get a string representation of an error type.
     *
     * @param int|string $type The error type constant or string
     *
     * @return string The string representation of the error type
     */
    public static function getErrorTypeString(int|string $type): string
    {
        switch ($type)
        {
            case E_ERROR: // 1 //
                return 'E_ERROR';

            case E_WARNING: // 2 //
                return 'E_WARNING';

            case E_PARSE: // 4 //
                return 'E_PARSE';

            case E_NOTICE: // 8 //
                return 'E_NOTICE';

            case E_CORE_ERROR: // 16 //
                return 'E_CORE_ERROR';

            case E_CORE_WARNING: // 32 //
                return 'E_CORE_WARNING';

            case E_COMPILE_ERROR: // 64 //
                return 'E_COMPILE_ERROR';

            case E_COMPILE_WARNING: // 128 //
                return 'E_COMPILE_WARNING';

            case E_USER_ERROR: // 256 //
                return 'E_USER_ERROR';

            case E_USER_WARNING: // 512 //
                return 'E_USER_WARNING';

            case E_USER_NOTICE: // 1024 //
                return 'E_USER_NOTICE';

            case E_STRICT: // 2048 //
                return 'E_STRICT';

            case E_RECOVERABLE_ERROR: // 4096 //
                return 'E_RECOVERABLE_ERROR';

            case E_DEPRECATED: // 8192 //
                return 'E_DEPRECATED';

            case E_USER_DEPRECATED: // 16384 //
                return 'E_USER_DEPRECATED';

            default:
                return (string) $type;
        }
    }

    public static function scrubState(mixed &$item): void
    {
        if (!is_array($item))
        {
            return;
        }

        foreach ($item as $key => $value)
        {
            if (is_null($value))
            {
                continue;
            }

            if (is_object($value))
            {
                if ($value instanceof EntityCore)
                {
                    $item[$key] = get_object_vars($value);
                }
                else
                {
                    $item[$key] = 'object';
                }

                continue;
            }

            if (is_string($value) && !mb_detect_encoding($value, 'ASCII', true))
            {
                $item[$key] = 'binary';

                continue;
            }
            if ($key === 'database')
            {
                $item[$key] = 'scrubbed';

                continue;
            }
            if ($key === 'databases')
            {
                $item[$key] = 'scrubbed';

                continue;
            }
            if ($key === 'config')
            {
                $item[$key] = 'scrubbed';

                continue;
            }

            if (is_array($value))
            {
                self::scrubState($item[$key]);
            }
        }
    }
}
