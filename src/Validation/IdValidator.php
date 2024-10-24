<?php

/**
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Validation;

/**
 * Class IdValidator.
 *
 * This class extends CustomValidator to provide ID validation functionality.
 */
class IdValidator extends CustomValidator
{
    /**
     * IdValidator constructor.
     *
     * @param string $name The name of the field to validate
     */
    public function __construct(
        string $name,
    ) {
        parent::__construct($name);

        $this->filter(FORMAT_ID)->default(null);
    }

    /**
     * Convert the validated string value to an integer or null.
     *
     * @param string $value The value to convert
     *
     * @return null|int The converted integer value or null if empty
     */
    public function getTyped(string $value): mixed
    {
        if (!strlen($value))
        {
            return null;
        }

        return (int) $value;
    }
}
