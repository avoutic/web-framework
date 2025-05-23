<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Validation\Validator;

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
     * @param string $name The name to use for error messages (default: 'email')
     */
    public function __construct(
        string $name = 'email',
    ) {
        parent::__construct($name);

        $this->email()->maxLength(255);
    }
}
