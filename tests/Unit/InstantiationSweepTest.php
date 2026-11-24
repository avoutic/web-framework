<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use DI\Container;
use WebFramework\Cache\Cache;
use WebFramework\Cache\NullCache;
use WebFramework\Mail\LoggingMailService;
use WebFramework\Mail\NullMailService;
use WebFramework\Mail\UserMailer;
use WebFramework\SanityCheck\SanityCheckBase;
use WebFramework\Security\Extension\NullLoginExtension;
use WebFramework\Security\RandomProvider;
use WebFramework\Testing\InstantiationSweep;

/**
 * @internal
 *
 * @covers \WebFramework\Testing\InstantiationSweep
 * @covers \WebFramework\Testing\InstantiationSweepResult
 */
final class InstantiationSweepTest extends Unit
{
    public function testInstantiatesTheClassesOfANamespace()
    {
        $result = (new InstantiationSweep(new Container()))->run(['WebFramework\Cache']);

        verify($result->instantiated)->same([NullCache::class]);
        verify($result->failed)->same([]);
        verify($result->isSuccessful())->true();
    }

    public function testInterfacesAndAbstractClassesAreNotTried()
    {
        $result = (new InstantiationSweep(new Container()))->run(['WebFramework\Cache', 'WebFramework\SanityCheck']);

        verify($result->instantiated)->arrayNotContains(Cache::class);
        verify($result->instantiated)->arrayNotContains(SanityCheckBase::class);
        verify(array_keys($result->failed))->arrayNotContains(SanityCheckBase::class);
    }

    public function testCollectsFailuresWithTheirReasonAndKeepsGoing()
    {
        // A bare container cannot give UserMailer its configured values
        $result = (new InstantiationSweep(new Container()))->run(['WebFramework\Mail']);

        verify($result->instantiated)->arrayContains(NullMailService::class);
        verify($result->failed)->arrayHasKey(UserMailer::class);
        verify($result->failed[UserMailer::class])->notEmpty();
        verify($result->isSuccessful())->false();
        verify($result->describeFailures())->stringContainsString(UserMailer::class);
    }

    public function testSkippedClassesAreNotTried()
    {
        $result = (new InstantiationSweep(new Container()))->run(
            ['WebFramework\Mail'],
            [UserMailer::class, 'WebFramework\Mail\QueuedMailService', LoggingMailService::class],
        );

        verify($result->instantiated)->same([NullMailService::class]);
        verify($result->failed)->same([]);
        verify($result->skipped)->arrayContains(UserMailer::class);
    }

    public function testNamespaceIsNotRecursiveByDefault()
    {
        $flat = (new InstantiationSweep(new Container()))->run(['WebFramework\Security'], [], false);
        $recursive = (new InstantiationSweep(new Container()))->run(['WebFramework\Security'], [], true);

        $all = fn ($result) => array_merge($result->instantiated, array_keys($result->failed));

        verify($all($flat))->arrayNotContains(NullLoginExtension::class);
        verify($all($recursive))->arrayContains(NullLoginExtension::class);
    }

    public function testNamespaceWithoutClassesIsReportedAndNotSuccessful()
    {
        $result = (new InstantiationSweep(new Container()))->run(['WebFramework\Cache', 'WebFramework\DoesNotExist']);

        verify($result->emptyNamespaces)->same(['WebFramework\DoesNotExist']);
        verify($result->isSuccessful())->false();
        verify($result->describeFailures())->stringContainsString('WebFramework\DoesNotExist');
    }

    public function testInterfaceInSkipListIsHarmless()
    {
        $result = (new InstantiationSweep(new Container()))->run(['WebFramework\Security'], [RandomProvider::class]);

        verify($result->skipped)->arrayNotContains(RandomProvider::class);
    }
}
