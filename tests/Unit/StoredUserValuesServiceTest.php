<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use WebFramework\Core\EntityCollection;
use WebFramework\Entity\StoredUserValue;
use WebFramework\Repository\StoredUserValueRepository;
use WebFramework\Support\StoredUserValuesService;

/**
 * @internal
 *
 * @coversNothing
 */
final class StoredUserValuesServiceTest extends Unit
{
    public function testGetValues()
    {
        $storedValue1 = $this->make(StoredUserValue::class, [
            'getName' => 'setting1',
            'getValue' => 'value1',
        ]);

        $storedValue2 = $this->make(StoredUserValue::class, [
            'getName' => 'setting2',
            'getValue' => 'value2',
        ]);

        $collection = new EntityCollection([$storedValue1, $storedValue2]);

        $repository = $this->make(StoredUserValueRepository::class, [
            'getValuesByUserAndModule' => $collection,
        ]);

        $service = new StoredUserValuesService($repository);
        $result = $service->getValues(123, 'testmodule');

        verify($result)->equals([
            'setting1' => 'value1',
            'setting2' => 'value2',
        ]);
    }

    public function testValueExistsTrue()
    {
        $storedValue = $this->make(StoredUserValue::class);

        $repository = $this->make(StoredUserValueRepository::class, [
            'getValue' => Expected::once($storedValue),
        ]);

        $service = new StoredUserValuesService($repository);
        $result = $service->valueExists(123, 'module.key');

        verify($result)->equals(true);
    }

    public function testValueExistsFalse()
    {
        $repository = $this->make(StoredUserValueRepository::class, [
            'getValue' => Expected::once(null),
        ]);

        $service = new StoredUserValuesService($repository);
        $result = $service->valueExists(123, 'module.key');

        verify($result)->equals(false);
    }

    public function testGetValueExists()
    {
        $storedValue = $this->make(StoredUserValue::class, [
            'getValue' => Expected::once('testvalue'),
        ]);

        $repository = $this->make(StoredUserValueRepository::class, [
            'getValue' => Expected::once($storedValue),
        ]);

        $service = new StoredUserValuesService($repository);
        $result = $service->getValue(123, 'module.key');

        verify($result)->equals('testvalue');
    }

    public function testGetValueNotExists()
    {
        $repository = $this->make(StoredUserValueRepository::class, [
            'getValue' => Expected::once(null),
        ]);

        $service = new StoredUserValuesService($repository);
        $result = $service->getValue(123, 'module.key');

        verify($result)->equals('');
    }

    public function testGetValueWithDefault()
    {
        $repository = $this->make(StoredUserValueRepository::class, [
            'getValue' => Expected::once(null),
        ]);

        $service = new StoredUserValuesService($repository);
        $result = $service->getValue(123, 'module.key', 'defaultvalue');

        verify($result)->equals('defaultvalue');
    }

    public function testSetValueNew()
    {
        $repository = $this->make(StoredUserValueRepository::class, [
            'getValue' => Expected::once(null),
            'save' => Expected::once(),
        ]);

        $service = new StoredUserValuesService($repository);
        $service->setValue(123, 'module.key', 'newvalue');
    }

    public function testSetValueExisting()
    {
        $storedValue = $this->make(StoredUserValue::class, [
            'setValue' => Expected::once(),
        ]);

        $repository = $this->make(StoredUserValueRepository::class, [
            'getValue' => Expected::once($storedValue),
            'save' => Expected::once(),
        ]);

        $service = new StoredUserValuesService($repository);
        $service->setValue(123, 'module.key', 'updatedvalue');
    }

    public function testDeleteValueExists()
    {
        $storedValue = $this->make(StoredUserValue::class);

        $repository = $this->make(StoredUserValueRepository::class, [
            'getValue' => Expected::once($storedValue),
            'delete' => Expected::once(),
        ]);

        $service = new StoredUserValuesService($repository);
        $service->deleteValue(123, 'module.key');
    }

    public function testDeleteValueNotExists()
    {
        $repository = $this->make(StoredUserValueRepository::class, [
            'getValue' => Expected::once(null),
            'delete' => Expected::never(),
        ]);

        $service = new StoredUserValuesService($repository);
        $service->deleteValue(123, 'module.key');
    }

    public function testMultipleUsersIsolation()
    {
        $user1Value = $this->make(StoredUserValue::class, [
            'getValue' => Expected::once('user1value'),
        ]);

        $user2Value = $this->make(StoredUserValue::class, [
            'getValue' => Expected::once('user2value'),
        ]);

        $callCount = 0;
        $repository = $this->make(StoredUserValueRepository::class, [
            'getValue' => function () use (&$callCount, $user1Value, $user2Value) {
                $callCount++;

                return $callCount === 1 ? $user1Value : $user2Value;
            },
        ]);

        $service = new StoredUserValuesService($repository);

        $result1 = $service->getValue(123, 'module.key');
        $result2 = $service->getValue(456, 'module.key');

        verify($result1)->equals('user1value');
        verify($result2)->equals('user2value');
    }
}
