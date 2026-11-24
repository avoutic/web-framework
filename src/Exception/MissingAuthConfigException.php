<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Exception;

/**
 * Exception thrown when an auth configuration file does not exist.
 */
class MissingAuthConfigException extends \RuntimeException
{
    /**
     * MissingAuthConfigException constructor.
     *
     * @param string $name The name of the auth configuration
     */
    public function __construct(
        private string $name,
    ) {
        parent::__construct("Auth Config {$name} does not exist");
    }

    /**
     * Get the name of the auth configuration that does not exist.
     */
    public function getName(): string
    {
        return $this->name;
    }
}
