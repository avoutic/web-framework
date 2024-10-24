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
 * Class CustomBoolValidator.
 *
 * This class extends CustomValidator to provide boolean validation functionality.
 */
class CustomBoolValidator extends CustomValidator
{
    /**
     * CustomBoolValidator constructor.
     *
     * @param string $name The name of the field to validate
     */
    public function __construct(
        string $name,
    ) {
        parent::__construct($name);

        $this->filter('0|1|true|false');
    }

    /**
     * Convert the validated string value to a boolean.
     *
     * @param string $value The value to convert
     *
     * @return bool The converted boolean value
     */
    public function getTyped(string $value): mixed
    {
        return ($value === '1' || $value === 'true');
    }
}
