<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Container\ContainerInterface as Container;
use Tests\Support\TestRepository;
use WebFramework\Database\Database;
use WebFramework\Database\DatabaseResultWrapper;
use WebFramework\Entity\QueueJob;
use WebFramework\Repository\QueueJobRepository;

/**
 * @internal
 *
 * @covers \WebFramework\Repository\RepositoryCore
 */
final class RepositoryCoreSaveTest extends Unit
{
    /** @var array<array{query: string, params: array<mixed>}> */
    private array $queries = [];

    /**
     * @return array<string, mixed>
     */
    private function row(): array
    {
        // Values as a database driver returns them: strings and NULLs
        return [
            'id' => '7',
            'queue_name' => 'default',
            'job_data' => '{}',
            'available_at' => '100',
            'created_at' => '100',
            'attempts' => '0',
            'reserved_at' => null,
            'max_attempts' => '3',
            'error' => null,
            'failed_at' => null,
            'completed_at' => null,
        ];
    }

    private function buildRepository(): QueueJobRepository
    {
        $this->queries = [];

        $database = $this->makeEmpty(Database::class, [
            'query' => function (string $query, array $params) {
                $this->queries[] = ['query' => $query, 'params' => $params];

                return $this->makeEmpty(DatabaseResultWrapper::class);
            },
        ]);

        return $this->construct(
            QueueJobRepository::class,
            [
                $this->makeEmpty(Container::class),
                $database,
            ]
        );
    }

    public function testNullColumnsAreInSnapshotAndValuesAreTyped()
    {
        $repository = $this->buildRepository();

        $job = $repository->instantiateEntityFromData($this->row());

        verify($job->getOriginalValues())->same([
            'id' => 7,
            'queue_name' => 'default',
            'job_data' => '{}',
            'available_at' => 100,
            'created_at' => 100,
            'attempts' => 0,
            'reserved_at' => null,
            'max_attempts' => 3,
            'error' => null,
            'failed_at' => null,
            'completed_at' => null,
        ]);
    }

    public function testUnchangedEntityHasNoChangedFields()
    {
        $repository = $this->buildRepository();

        $job = $repository->instantiateEntityFromData($this->row());

        verify($repository->getChangedFields($job))->same([]);

        $repository->save($job);

        verify($this->queries)->same([]);
    }

    public function testSaveOnlyWritesTheChangedField()
    {
        $repository = $this->buildRepository();

        $job = $repository->instantiateEntityFromData($this->row());
        $job->setAttempts(1);

        $repository->save($job);

        verify(count($this->queries))->equals(1);
        verify($this->queries[0]['query'])->stringContainsString('SET `attempts` = ?');
        verify($this->queries[0]['query'])->stringNotContainsString('reserved_at');
        verify($this->queries[0]['params'])->same([1, 7]);
    }

    public function testSavingTwiceDoesNotRewriteTheSameChange()
    {
        $repository = $this->buildRepository();

        $job = $repository->instantiateEntityFromData($this->row());
        $job->setAttempts(1);

        $repository->save($job);
        $repository->save($job);

        verify(count($this->queries))->equals(1);
        verify($repository->getChangedFields($job))->same([]);
    }

    public function testChangeFromEmptyStringToNullIsSaved()
    {
        $repository = $this->buildRepository();

        $row = $this->row();
        $row['error'] = '';

        $job = $repository->instantiateEntityFromData($row);
        $job->setError(null);

        verify($repository->getChangedFields($job))->same(['error' => null]);

        $repository->save($job);

        verify(count($this->queries))->equals(1);
        verify($this->queries[0]['query'])->stringContainsString('SET `error` = NULL');
    }

    public function testChangeFromNullToValueIsSaved()
    {
        $repository = $this->buildRepository();

        $job = $repository->instantiateEntityFromData($this->row());
        $job->setReservedAt(500);

        verify($repository->getChangedFields($job))->same(['reserved_at' => 500]);
    }

    public function testNullForNonNullablePropertyKeepsDefaultAndIsNotWrittenBack()
    {
        $repository = $this->construct(
            TestRepository::class,
            [
                $this->makeEmpty(Container::class),
                $this->makeEmpty(Database::class, [
                    'query' => Expected::never(),
                ]),
            ]
        );

        $entity = $repository->instantiateEntityFromData([
            'id' => '3',
            'name' => null,
            'email' => 'test@example.com',
            'age' => '30',
            'active' => '1',
            'secret_field' => 's',
            'created_at' => '100',
        ]);

        verify($entity->getName())->same('');
        verify($repository->getChangedFields($entity))->same([]);

        $repository->save($entity);
    }

    public function testNewEntityIsNotRewrittenAfterInsert()
    {
        $created = $this->buildRepository()->instantiateEntityFromData($this->row());

        $database = $this->makeEmpty(Database::class, [
            'query' => Expected::never(),
        ]);

        $repository = $this->construct(
            QueueJobRepository::class,
            [
                $this->makeEmpty(Container::class),
                $database,
            ],
            [
                'create' => Expected::once($created),
            ]
        );

        $job = new QueueJob();
        $job->setQueueName('default');
        $job->setJobData('{}');
        $job->setAvailableAt(100);
        $job->setCreatedAt(100);
        $job->setAttempts(0);
        $job->setMaxAttempts(3);

        $repository->save($job);

        verify($job->getId())->equals(7);
        verify($repository->getChangedFields($job))->same([]);

        $repository->save($job);
    }

    /**
     * @param null|array<string, mixed> $dbRow The row the database returns, null for no row
     */
    private function buildRepositoryWithRow(?array $dbRow): QueueJobRepository
    {
        $this->queries = [];

        $database = $this->makeEmpty(Database::class, [
            'query' => function (string $query, array $params) use ($dbRow) {
                $this->queries[] = ['query' => $query, 'params' => $params];

                $result = $this->makeEmpty(DatabaseResultWrapper::class, [
                    'RecordCount' => ($dbRow === null) ? 0 : 1,
                ]);
                $result->fields = $dbRow ?? [];

                return $result;
            },
        ]);

        return $this->construct(
            QueueJobRepository::class,
            [
                $this->makeEmpty(Container::class),
                $database,
            ]
        );
    }

    public function testRefreshLoadsTheCurrentValuesIntoTheSameInstance()
    {
        $current = $this->row();
        $current['attempts'] = '2';
        $current['error'] = 'failed before';
        $current['reserved_at'] = '500';

        $repository = $this->buildRepositoryWithRow($current);

        $job = $repository->instantiateEntityFromData($this->row());
        $reference = $job;

        $repository->refresh($job);

        verify($reference->getAttempts())->same(2);
        verify($reference->getError())->same('failed before');
        verify($reference->getReservedAt())->same(500);
        verify($this->queries[0]['params'])->same([7]);
        verify($this->queries[0]['query'])->stringNotContainsString('FOR UPDATE');
    }

    public function testRefreshResetsTheBaselineAndDropsUnsavedChanges()
    {
        $repository = $this->buildRepositoryWithRow($this->row());

        $job = $repository->instantiateEntityFromData($this->row());
        $job->setAttempts(9);

        $repository->refresh($job);

        verify($job->getAttempts())->same(0);
        verify($repository->getChangedFields($job))->same([]);
    }

    public function testRefreshSetsAColumnThatBecameNull()
    {
        $before = $this->row();
        $before['error'] = 'old error';

        $repository = $this->buildRepositoryWithRow($this->row());

        $job = $repository->instantiateEntityFromData($before);

        $repository->refresh($job);

        verify($job->getError())->null();
    }

    public function testRefreshCanLockTheRow()
    {
        $repository = $this->buildRepositoryWithRow($this->row());

        $job = $repository->instantiateEntityFromData($this->row());

        $repository->refresh($job, true);

        verify($this->queries[0]['query'])->stringContainsString('FOR UPDATE');
    }

    public function testRefreshThrowsWhenTheRowIsGone()
    {
        $repository = $this->buildRepositoryWithRow(null);

        $job = $repository->instantiateEntityFromData($this->row());

        verify(function () use ($repository, $job) {
            $repository->refresh($job);
        })->callableThrows(\RuntimeException::class);
    }

    public function testEntityResetToNewIsInsertedAgain()
    {
        // The row that the database returns for each insert
        $first = $this->buildRepository()->instantiateEntityFromData($this->row());
        $secondRow = $this->row();
        $secondRow['id'] = '8';
        $second = $this->buildRepository()->instantiateEntityFromData($secondRow);

        $created = [$first, $second];

        $repository = $this->construct(
            QueueJobRepository::class,
            [
                $this->makeEmpty(Container::class),
                $this->makeEmpty(Database::class, [
                    'query' => Expected::never(),
                ]),
            ],
            [
                'create' => Expected::exactly(2, function () use (&$created) {
                    return array_shift($created);
                }),
            ]
        );

        $job = new QueueJob();
        $job->setQueueName('default');
        $job->setJobData('{}');
        $job->setAvailableAt(100);
        $job->setCreatedAt(100);
        $job->setAttempts(0);
        $job->setMaxAttempts(3);

        $repository->save($job);
        verify($job->getId())->equals(7);

        // The transaction was rolled back: the row does not exist
        $job->resetToNew();

        $repository->save($job);

        verify($job->getId())->equals(8);
        verify($job->isNewObject())->false();
    }
}
