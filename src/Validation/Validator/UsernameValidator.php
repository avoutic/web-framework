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
 * Class UsernameValidator.
 *
 * This class extends CustomValidator to provide username validation functionality.
 */
class UsernameValidator extends CustomValidator
{
    /**
     * UsernameValidator constructor.
     *
     * @param string $name The name of the field to validate (default: 'username')
     */
    public function __construct(
        string $name = 'username',
    ) {
        parent::__construct($name);

        $this->filter(FORMAT_USERNAME)->maxLength(255);
    }
}
