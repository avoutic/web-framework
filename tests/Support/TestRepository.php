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
        return $this->getObject(['name' => $name]);
    }

    public function getTestEntityByEmail(string $email): ?TestEntity
    {
        return $this->getObject(['email' => $email]);
    }

    public function getActiveEntities(): array
    {
        return $this->getObjects(['active' => true]);
    }
}
