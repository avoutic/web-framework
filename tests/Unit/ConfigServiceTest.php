<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Core\RuntimeEnvironment;
use WebFramework\Security\ConfigService as SecureConfigService;

/**
 * @internal
 *
 * @coversNothing
 */
final class ConfigServiceTest extends Unit
{
    public function testGetAuthConfigNonExisting()
    {
        $instance = $this->construct(
            SecureConfigService::class,
            [
                $this->makeEmpty(RuntimeEnvironment::class, ['getAppDir' => __DIR__]),
                '/auth',
            ],
        );

        verify(function () use ($instance) {
            $instance->getAuthConfig('noname');
        })
            ->callableThrows(\RuntimeException::class)
        ;
    }

    public function testGetAuthConfigString()
    {
        $instance = $this->construct(
            SecureConfigService::class,
            [
                $this->makeEmpty(RuntimeEnvironment::class, ['getAppDir' => __DIR__]),
                '/auth',
            ],
        );

        verify($instance->getAuthConfig('string_value'))
            ->equals('TestString')
        ;
    }

    public function testGetAuthConfigArray()
    {
        $instance = $this->construct(
            SecureConfigService::class,
            [
                $this->makeEmpty(RuntimeEnvironment::class, ['getAppDir' => __DIR__]),
                '/auth',
            ],
        );

        verify($instance->getAuthConfig('array_value'))
            ->equals(['key1' => 'val1'])
        ;
    }
}
