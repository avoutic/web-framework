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

use WebFramework\Validation\Rule\UrlRule;

/**
 * Class UrlValidator.
 *
 * This class extends CustomValidator to provide url validation functionality.
 */
class UrlValidator extends CustomValidator
{
    /**
     * UrlValidator constructor.
     *
     * @param string $name The name to use for error messages (default: 'url')
     */
    public function __construct(
        string $name = 'url',
    ) {
        parent::__construct($name);

        $this->addRule(new UrlRule());
    }
}
