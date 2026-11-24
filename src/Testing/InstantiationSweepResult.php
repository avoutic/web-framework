<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Testing;

/**
 * The outcome of an InstantiationSweep.
 */
class InstantiationSweepResult
{
    /**
     * @param array<class-string>         $instantiated    The classes the container could instantiate
     * @param array<class-string, string> $failed          The classes it could not instantiate, with the reason
     * @param array<class-string>         $skipped         The instantiable classes that were skipped on request
     * @param array<string>               $emptyNamespaces The namespaces in which no class was found at all
     */
    public function __construct(
        public readonly array $instantiated,
        public readonly array $failed,
        public readonly array $skipped,
        public readonly array $emptyNamespaces,
    ) {}

    /**
     * Check that every class could be instantiated and every namespace contained classes.
     *
     * A namespace without classes is not successful, as that usually is a typo or a
     * moved namespace, which would silently make the sweep check nothing.
     */
    public function isSuccessful(): bool
    {
        return !count($this->failed) && !count($this->emptyNamespaces);
    }

    /**
     * Describe what went wrong, as a message for a failing test.
     *
     * @return string One line per problem, or an empty string if there is none
     */
    public function describeFailures(): string
    {
        $lines = [];

        foreach ($this->emptyNamespaces as $namespace)
        {
            $lines[] = "No classes found in namespace {$namespace}";
        }

        foreach ($this->failed as $class => $reason)
        {
            $lines[] = "{$class}: {$reason}";
        }

        return implode(PHP_EOL, $lines);
    }
}
