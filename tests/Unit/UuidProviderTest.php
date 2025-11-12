<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use WebFramework\Security\RandomProvider;
use WebFramework\Support\UuidProvider;

/**
 * @internal
 *
 * @covers \WebFramework\Support\UuidProvider
 */
final class UuidProviderTest extends Unit
{
    public function testGenerateUuidRfc4122TestVector()
    {
        $uuidProvider = $this->make(
            UuidProvider::class,
            [
                'randomProvider' => $this->makeEmpty(
                    RandomProvider::class,
                    [
                        'getRandom' => Expected::once(hex2bin('919108f752d133205bacf847db4148a8')),
                    ],
                ),
            ],
        );

        verify($uuidProvider->generate())
            ->equals('919108f7-52d1-4320-9bac-f847db4148a8')
        ;
    }

    public function testGenerateUuid()
    {
        $uuidProvider = $this->make(
            UuidProvider::class,
            [
                'randomProvider' => $this->makeEmpty(
                    RandomProvider::class,
                    [
                        'getRandom' => Expected::once(hex2bin('12345678901234567890123456789012')),
                    ],
                ),
            ],
        );

        verify($uuidProvider->generate())
            ->equals('12345678-9012-4456-b890-123456789012')
        ;
    }

    public function testGenerateUuid2()
    {
        $uuidProvider = $this->make(
            UuidProvider::class,
            [
                'randomProvider' => $this->makeEmpty(
                    RandomProvider::class,
                    [
                        'getRandom' => Expected::once(hex2bin('00000000000000000000000000000000')),
                    ],
                ),
            ],
        );

        verify($uuidProvider->generate())
            ->equals('00000000-0000-4000-8000-000000000000')
        ;
    }

    public function testGenerateUuidInvalidRandom()
    {
        $uuidProvider = $this->make(
            UuidProvider::class,
            [
                'randomProvider' => $this->makeEmpty(
                    RandomProvider::class,
                    [
                        'getRandom' => Expected::once(hex2bin('0000000000000000')),
                    ],
                ),
            ],
        );

        verify(function () use ($uuidProvider) {
            $uuidProvider->generate();
        })
            ->callableThrows(\RuntimeException::class)
        ;
    }
}
