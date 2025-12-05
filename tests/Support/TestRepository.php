<?php

namespace Tests\Support;

use WebFramework\Repository\RepositoryCore;

/**
 * @extends RepositoryCore<TestEntity>
 */
class TestRepository extends RepositoryCore
{
    /** @var class-string<TestEntity> */
    protected static string $entityClass = TestEntity::class;
    
    protected static string $tableName = 'test_entities';
    protected static array $baseFields = ['name', 'email', 'age', 'active', 'secret_field', 'created_at'];

    public function getTestEntityByName(string $name): ?TestEntity
    {
        return $this->query()
            ->where(['name' => $name])
            ->getOne()
        ;
    }

    public function getTestEntityByEmail(string $email): ?TestEntity
    {
        return $this->query()
            ->where(['email' => $email])
            ->getOne()
        ;
    }

    public function getActiveEntities(): array
    {
        return $this->query()
            ->where(['active' => true])
            ->execute();
    }
}
