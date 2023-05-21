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
                '/appdir/auth',
            ],
            [
                'load_file' => function ($filename) { throw new \RuntimeException(); },
            ],
        );

        verify(function () use ($instance) {
            $instance->get_auth_config('noname');
        })
            ->callableThrows(\RuntimeException::class);
    }

    public function testGetAuthConfigEmpty()
    {
        $instance = $this->construct(
            SecureConfigService::class,
            [
                '/appdir/auth',
            ],
            [
                'load_file' => '',
            ],
        );

        verify(function () use ($instance) {
            $instance->get_auth_config('noname');
        })
            ->callableThrows(\RuntimeException::class, 'Auth Config noname invalid');
    }

    public function testGetAuthConfigString()
    {
        $instance = $this->construct(
            SecureConfigService::class,
            [
                '/appdir/auth',
            ],
            [
                'load_file' => 'TestString',
            ],
        );

        verify($instance->get_auth_config('noname'))
            ->equals('TestString');
    }

    public function testGetAuthConfigArray()
    {
        $instance = $this->construct(
            SecureConfigService::class,
            [
                '/appdir/auth',
            ],
            [
                'load_file' => ['key1' => 'val1'],
            ],
        );

        verify($instance->get_auth_config('noname'))
            ->equals(['key1' => 'val1']);
    }
}
