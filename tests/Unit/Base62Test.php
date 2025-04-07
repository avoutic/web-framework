<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use Tests\Support\UnitTester;
use WebFramework\Support\Base62;

/**
 * @internal
 *
 * @coversNothing
 */
final class Base62Test extends Unit
{
    protected UnitTester $tester;

    protected function _before() {}

    // tests
    public function testVectors()
    {
        $instance = $this->construct(
            Base62::class,
        );

        verify($instance->encode(0))
            ->equals('')
        ;

        verify($instance->decode(''))
            ->equals(0)
        ;

        verify($instance->encode(1))
            ->equals('1')
        ;

        verify($instance->decode('1'))
            ->equals(1)
        ;

        verify($instance->encode(72624924005429))
            ->equals('kCBm3Vd3')
        ;

        verify($instance->decode('kCBm3Vd3'))
            ->equals(72624924005429)
        ;

        verify($instance->encode(4512359655645))
            ->equals('1hrrrg9T')
        ;

        verify($instance->decode('1hrrrg9T'))
            ->equals(4512359655645)
        ;

        verify($instance->encode(17152000080397))
            ->equals('4RYaUnp3')
        ;

        verify($instance->decode('4RYaUnp3'))
            ->equals(17152000080397)
        ;

        verify($instance->encode(122763989416227))
            ->equals('yRkocIn1')
        ;

        verify($instance->decode('yRkocIn1'))
            ->equals(122763989416227)
        ;
    }

    public function testIllegalCharacter()
    {
        $instance = $this->construct(
            Base62::class,
        );

        verify(function () use ($instance) {
            $instance->decode('@');
        })
            ->callableThrows(\InvalidArgumentException::class)
        ;
    }
}
