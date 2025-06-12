<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use Tests\Support\TestEntity;
use WebFramework\Core\EntityCore;
use WebFramework\Support\Helpers;

/**
 * @internal
 *
 * @coversNothing
 */
final class HelpersTest extends Unit
{
    public function testScrubStateNotArray()
    {
        $item = 'string';
        Helpers::scrubState($item);
        verify($item)->equals('string');

        $item = 123;
        Helpers::scrubState($item);
        verify($item)->equals(123);

        $item = null;
        Helpers::scrubState($item);
        verify($item)->equals(null);
    }

    public function testScrubStateEmptyArray()
    {
        $item = [];
        Helpers::scrubState($item);
        verify($item)->equals([]);
    }

    public function testScrubStateWithNullValues()
    {
        $item = [
            'key1' => null,
            'key2' => 'value',
        ];
        Helpers::scrubState($item);
        verify($item)->equals([
            'key1' => null,
            'key2' => 'value',
        ]);
    }

    public function testScrubStateWithEntityCore()
    {
        $entity = new TestEntity();
        $entity->setName('value1');

        $item = [
            'entity' => $entity,
            'other' => 'value',
        ];

        Helpers::scrubState($item);

        verify($item['entity'])->equals([]);
        verify($item['other'])->equals('value');
    }

    public function testScrubStateWithRegularObject()
    {
        $object = new \stdClass();
        $object->prop = 'value';

        $item = [
            'object' => $object,
            'other' => 'value',
        ];

        Helpers::scrubState($item);

        verify($item['object'])->equals('object');
        verify($item['other'])->equals('value');
    }

    public function testScrubStateWithBinaryString()
    {
        $item = [
            'binary' => "\x00\x01\x02\x03",
            'text' => 'normal text',
        ];

        Helpers::scrubState($item);

        verify($item['binary'])->equals("\x00\x01\x02\x03");
        verify($item['text'])->equals('normal text');
    }

    public function testScrubStateWithDatabaseKey()
    {
        $item = [
            'database' => 'sensitive_connection_info',
            'other' => 'value',
        ];

        Helpers::scrubState($item);

        verify($item['database'])->equals('scrubbed');
        verify($item['other'])->equals('value');
    }

    public function testScrubStateWithConfigKey()
    {
        $item = [
            'config' => ['secret' => 'password'],
            'other' => 'value',
        ];

        Helpers::scrubState($item);

        verify($item['config'])->equals('scrubbed');
        verify($item['other'])->equals('value');
    }

    public function testScrubStateNestedArrays()
    {
        $item = [
            'level1' => [
                'level2' => [
                    'database' => 'secret',
                    'normal' => 'value',
                ],
                'config' => 'sensitive',
            ],
            'top' => 'value',
        ];

        Helpers::scrubState($item);

        verify($item['level1']['level2']['database'])->equals('scrubbed');
        verify($item['level1']['level2']['normal'])->equals('value');
        verify($item['level1']['config'])->equals('scrubbed');
        verify($item['top'])->equals('value');
    }

    public function testScrubStateComplexMixed()
    {
        $entity = $this->makeEmpty(EntityCore::class, [
            'toArray' => [
                'id' => 123,
                'name' => 'test',
            ],
        ]);

        $object = new \stdClass();
        $object->data = 'test';

        $item = [
            'entities' => [
                'user' => $entity,
                'metadata' => [
                    'database' => 'connection_string',
                    'object' => $object,
                    'binary' => "\xFF\xFE",
                ],
            ],
            'config' => ['api_key' => 'secret'],
            'normal' => 'text',
        ];

        Helpers::scrubState($item);

        verify($item['entities']['user'])->equals([]);
        verify($item['entities']['metadata']['database'])->equals('scrubbed');
        verify($item['entities']['metadata']['object'])->equals('object');
        verify($item['entities']['metadata']['binary'])->equals('binary');
        verify($item['config'])->equals('scrubbed');
        verify($item['normal'])->equals('text');
    }

    public function testScrubStateWithUnicodeString()
    {
        $item = [
            'unicode' => 'Hello 世界 🌍',
            'ascii' => 'Hello World',
        ];

        Helpers::scrubState($item);

        verify($item['unicode'])->equals('binary');
        verify($item['ascii'])->equals('Hello World');
    }

    public function testScrubStateDeepNesting()
    {
        $item = [
            'a' => [
                'b' => [
                    'c' => [
                        'd' => [
                            'database' => 'deep_secret',
                            'value' => 'normal',
                        ],
                    ],
                ],
            ],
        ];

        Helpers::scrubState($item);

        verify($item['a']['b']['c']['d']['database'])->equals('scrubbed');
        verify($item['a']['b']['c']['d']['value'])->equals('normal');
    }
}
