<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Config\ConfigService;

/**
 * @internal
 *
 * @coversNothing
 */
final class ConfigServiceTest extends Unit
{
    public function testGetSimpleValue()
    {
        $config = [
            'debug' => true,
            'timezone' => 'UTC',
        ];

        $service = new ConfigService($config);

        verify($service->get('debug'))->equals(true);
        verify($service->get('timezone'))->equals('UTC');
    }

    public function testGetNestedValue()
    {
        $config = [
            'security' => [
                'hash' => 'sha256',
                'blacklist' => [
                    'threshold' => 25,
                    'trigger_period' => 14400,
                ],
            ],
            'authenticator' => [
                'unique_identifier' => 'email',
                'session_timeout' => 900,
            ],
        ];

        $service = new ConfigService($config);

        verify($service->get('security.hash'))->equals('sha256');
        verify($service->get('security.blacklist.threshold'))->equals(25);
        verify($service->get('security.blacklist.trigger_period'))->equals(14400);
        verify($service->get('authenticator.unique_identifier'))->equals('email');
    }

    public function testGetArrayValue()
    {
        $config = [
            'middlewares' => [
                'pre_routing' => ['Middleware1', 'Middleware2'],
                'post_routing' => ['Middleware3'],
            ],
        ];

        $service = new ConfigService($config);

        verify($service->get('middlewares.pre_routing'))->equals(['Middleware1', 'Middleware2']);
        verify($service->get('middlewares.post_routing'))->equals(['Middleware3']);
    }

    public function testGetNonExistentKey()
    {
        $config = [
            'existing_key' => 'value',
        ];

        $service = new ConfigService($config);

        verify(function () use ($service) {
            $service->get('non_existent_key');
        })->callableThrows(\InvalidArgumentException::class);
    }

    public function testGetNonExistentNestedKey()
    {
        $config = [
            'security' => [
                'hash' => 'sha256',
            ],
        ];

        $service = new ConfigService($config);

        verify(function () use ($service) {
            $service->get('security.non_existent');
        })->callableThrows(\InvalidArgumentException::class);
    }

    public function testGetDeepNonExistentKey()
    {
        $config = [
            'level1' => [
                'level2' => 'value',
            ],
        ];

        $service = new ConfigService($config);

        verify(function () use ($service) {
            $service->get('level1.level2.level3');
        })->callableThrows(\InvalidArgumentException::class);
    }

    public function testGetWithNumericKeys()
    {
        $config = [
            'routes' => [
                0 => 'RouteClass1',
                1 => 'RouteClass2',
            ],
        ];

        $service = new ConfigService($config);

        verify($service->get('routes.0'))->equals('RouteClass1');
        verify($service->get('routes.1'))->equals('RouteClass2');
    }

    public function testGetEmptyStringKeyReturnsFullConfig()
    {
        $config = [
            'key' => 'value',
        ];

        $service = new ConfigService($config);

        verify($service->get(''))->equals([
            'key' => 'value',
        ]);
    }

    public function testGetWithNullValue()
    {
        $config = [
            'nullable_setting' => null,
        ];

        $service = new ConfigService($config);

        verify($service->get('nullable_setting'))->equals(null);
    }

    public function testGetWithZeroValue()
    {
        $config = [
            'zero_value' => 0,
            'false_value' => false,
            'empty_string' => '',
        ];

        $service = new ConfigService($config);

        verify($service->get('zero_value'))->equals(0);
        verify($service->get('false_value'))->equals(false);
        verify($service->get('empty_string'))->equals('');
    }
}
