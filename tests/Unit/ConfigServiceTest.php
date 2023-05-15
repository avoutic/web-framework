<?php

namespace Tests\Unit;

use WebFramework\Core\Security\ConfigService;

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
            ConfigService::class,
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
            ConfigService::class,
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
            ConfigService::class,
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
            ConfigService::class,
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
