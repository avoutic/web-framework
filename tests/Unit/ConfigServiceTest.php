<?php

namespace Tests\Unit;

use WebFramework\Security\ConfigService as SecureConfigService;

/**
 * @internal
 *
 * @coversNothing
 */
final class ConfigServiceTest extends \Codeception\Test\Unit
{
    public function testGetAuthConfigNonExisting()
    {
        $instance = $this->construct(
            SecureConfigService::class,
            [
                '/appdir',
                '/auth',
            ],
            [
                'loadFile' => function ($filename) { throw new \RuntimeException(); },
            ],
        );

        verify(function () use ($instance) {
            $instance->getAuthConfig('noname');
        })
            ->callableThrows(\RuntimeException::class);
    }

    public function testGetAuthConfigEmpty()
    {
        $instance = $this->construct(
            SecureConfigService::class,
            [
                '/appdir',
                '/auth',
            ],
            [
                'loadFile' => '',
            ],
        );

        verify(function () use ($instance) {
            $instance->getAuthConfig('noname');
        })
            ->callableThrows(\RuntimeException::class, 'Auth Config noname invalid');
    }

    public function testGetAuthConfigString()
    {
        $instance = $this->construct(
            SecureConfigService::class,
            [
                '/appdir',
                '/auth',
            ],
            [
                'loadFile' => 'TestString',
            ],
        );

        verify($instance->getAuthConfig('noname'))
            ->equals('TestString');
    }

    public function testGetAuthConfigArray()
    {
        $instance = $this->construct(
            SecureConfigService::class,
            [
                '/appdir',
                '/auth',
            ],
            [
                'loadFile' => ['key1' => 'val1'],
            ],
        );

        verify($instance->getAuthConfig('noname'))
            ->equals(['key1' => 'val1']);
    }
}
