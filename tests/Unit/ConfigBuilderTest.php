<?php

namespace Tests\Unit;

use WebFramework\Core\ConfigBuilder;

/**
 * @internal
 *
 * @coversNothing
 */
final class ConfigBuilderTest extends \Codeception\Test\Unit
{
    public function testMergeConfigEmpty()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                '/appdir/auth',
            ],
        );

        $instance->merge_config_on_top([]);

        verify($instance->get_config())
            ->equals([]);
    }

    public function testMergeConfigSeparate()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                '/appdir/auth',
            ],
        );

        $instance->merge_config_on_top([
            'key1' => 'val1',
        ]);

        $instance->merge_config_on_top([
            'key2' => 'val2',
        ]);

        verify($instance->get_config())
            ->equals([
                'key1' => 'val1',
                'key2' => 'val2',
            ]);
    }

    public function testMergeConfigSeparateDeep()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                '/appdir/auth',
            ],
        );

        $instance->merge_config_on_top([
            'key1' => [
                'key1.1' => 'val1.1',
            ],
        ]);

        $instance->merge_config_on_top([
            'key1' => [
                'key1.2' => 'val1.2',
            ],
        ]);

        verify($instance->get_config())
            ->equals([
                'key1' => [
                    'key1.1' => 'val1.1',
                    'key1.2' => 'val1.2',
                ],
            ]);
    }

    public function testMergeConfigOverwrite()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                '/appdir/auth',
            ],
        );

        $instance->merge_config_on_top([
            'key1' => 'val1',
            'key2' => 'val2',
        ]);

        $instance->merge_config_on_top([
            'key2' => 'val3',
        ]);

        verify($instance->get_config())
            ->equals([
                'key1' => 'val1',
                'key2' => 'val3',
            ]);
    }

    public function testMergeConfigOverwriteDeep()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                '/appdir/auth',
            ],
        );

        $instance->merge_config_on_top([
            'key1' => [
                'key1.1' => 'val1.1',
                'key1.2' => 'val1.2',
            ],
        ]);

        $instance->merge_config_on_top([
            'key1' => [
                'key1.2' => 'val1.3',
            ],
        ]);

        verify($instance->get_config())
            ->equals([
                'key1' => [
                    'key1.1' => 'val1.1',
                    'key1.2' => 'val1.3',
                ],
            ]);
    }

    public function testPopulateInternalsApp()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                '/appdir/auth',
            ],
        );

        $instance->populate_internals('', '');

        verify($instance->get_config())
            ->equals([
                'server_name' => 'app',
                'host_name' => 'app',
            ]);
    }

    public function testPopulateInternalsDefault()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                '/appdir/auth',
            ],
        );

        $instance->populate_internals('ServerName', 'HostName');

        verify($instance->get_config())
            ->equals([
                'server_name' => 'ServerName',
                'host_name' => 'HostName',
            ]);
    }

    public function testPopulateInternalsServerConfigured()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                '/appdir/auth',
            ],
        );

        $instance->populate_internals('ServerName', 'HostName');

        $instance->merge_config_on_top([
            'server_name' => 'MyServer',
        ]);

        verify($instance->get_config())
            ->equals([
                'server_name' => 'MyServer',
                'host_name' => 'HostName',
            ]);
    }

    public function testPopulateInternalsHostConfigured()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                '/appdir/auth',
            ],
        );

        $instance->populate_internals('ServerName', 'HostName');

        $instance->merge_config_on_top([
            'host_name' => 'MyHost',
        ]);

        verify($instance->get_config())
            ->equals([
                'server_name' => 'ServerName',
                'host_name' => 'MyHost',
            ]);
    }
}
