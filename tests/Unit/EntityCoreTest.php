<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use Tests\Support\TestEntity;

/**
 * @internal
 *
 * @coversNothing
 */
final class EntityCoreTest extends Unit
{
    public function testToArrayNewObject()
    {
        $entity = new TestEntity();
        $entity->setName('John Doe');
        $entity->setEmail('john@example.com');
        $entity->setAge(30);
        $entity->setActive(true);
        $entity->setSecretField('secret');
        $entity->setCreatedAt(1234567890);

        $result = $entity->toArray();

        verify(array_key_exists('id', $result))->equals(false);

        verify($result['name'])->equals('John Doe');
        verify($result['email'])->equals('john@example.com');
        verify($result['age'])->equals(30);
        verify($result['active'])->equals(true);
        verify($result['created_at'])->equals(1234567890);

        verify(array_key_exists('secret_field', $result))->equals(false);
    }

    public function testToArrayExistingObject()
    {
        $entity = new TestEntity();
        $entity->setObjectId(123);
        $entity->setName('Jane Doe');

        $result = $entity->toArray();

        verify($result['id'])->equals(123);
        verify($result['name'])->equals('Jane Doe');
    }

    public function testToRawArray()
    {
        $entity = new TestEntity();
        $entity->setObjectId(123);
        $entity->setName('John Doe');
        $entity->setSecretField('secret');

        $result = $entity->toRawArray();

        verify($result['id'])->equals(123);
        verify($result['name'])->equals('John Doe');
        verify(array_key_exists('secret_field', $result))->equals(false);
    }

    public function testFromArrayWithFillableFields()
    {
        $entity = new TestEntity();
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'age' => 25,
            'active' => true,
            'secret_field' => 'should_not_be_set',
        ];

        $entity->fromArray($data);

        verify($entity->getName())->equals('Test User');
        verify($entity->getEmail())->equals('test@example.com');
        verify($entity->getAge())->equals(25);
        verify($entity->isActive())->equals(true);
        verify($entity->getSecretField())->equals('');
    }

    public function testFromArrayWithExclusions()
    {
        $entity = new TestEntity();
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'age' => 25,
        ];

        $entity->fromArray($data, ['email']);

        verify($entity->getName())->equals('Test User');
        verify($entity->getEmail())->equals('');
        verify($entity->getAge())->equals(25);
    }

    public function testIsNewObject()
    {
        $entity = new TestEntity();
        verify($entity->isNewObject())->equals(true);

        $entity->setObjectId(123);
        verify($entity->isNewObject())->equals(false);
    }

    public function testSetObjectIdOnExistingObject()
    {
        $entity = new TestEntity();
        $entity->setObjectId(123);

        verify(function () use ($entity) {
            $entity->setObjectId(456);
        })->callableThrows(\RuntimeException::class, 'Id already set');
    }

    public function testGetCacheId()
    {
        $entity = new TestEntity();
        $entity->setObjectId(123);

        verify($entity->getCacheId())->equals('test_entities[123]');
    }

    public function testGetCacheIdFor()
    {
        verify(TestEntity::getCacheIdFor(456))->equals('test_entities[456]');
    }

    public function testOriginalValues()
    {
        $entity = new TestEntity();
        $originalValues = ['name' => 'Original Name', 'age' => 20];

        $entity->setOriginalValues($originalValues);
        verify($entity->getOriginalValues())->equals($originalValues);
    }

    public function testStaticMethods()
    {
        verify(TestEntity::getTableName())->equals('test_entities');
        verify(TestEntity::getBaseFields())->equals(['name', 'email', 'age', 'active', 'secret_field', 'created_at']);
        verify(TestEntity::getAdditionalIdFields())->equals([]);
    }

    public function testToStringAndDebugInfo()
    {
        $entity = new TestEntity();
        $entity->setName('Test');

        $toString = (string) $entity;
        $debugInfo = $entity->__debugInfo();

        verify($toString)->stringContainsString('Test');
        verify($debugInfo['name'])->equals('Test');
    }

    public function testToArrayWithUninitializedFields()
    {
        $entity = new TestEntity();
        $entity->setName('Test Name');

        $result = $entity->toArray();

        verify($result['name'])->equals('Test Name');
        verify($result['email'])->equals('');
        verify($result['age'])->equals(0);
        verify($result['active'])->equals(false);
    }

    public function testFromArrayWithMissingKeys()
    {
        $entity = new TestEntity();
        $data = [
            'name' => 'Test User',
        ];

        $entity->fromArray($data);

        verify($entity->getName())->equals('Test User');
        verify($entity->getEmail())->equals('');
        verify($entity->getAge())->equals(0);
        verify($entity->isActive())->equals(false);
    }
}
