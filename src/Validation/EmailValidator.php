<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Validation;

/**
 * Class EmailValidator.
 *
 * This class extends CustomValidator to provide email validation functionality.
 */
class EmailValidator extends CustomValidator
{
    /**
     * EmailValidator constructor.
     *
     * @param string $name The name of the field to validate (default: 'email')
     */
    public function __construct(
        string $name = 'email',
    ) {
        parent::__construct($name);

        $this->filter(FORMAT_EMAIL)->maxLength(255);
    }
}
