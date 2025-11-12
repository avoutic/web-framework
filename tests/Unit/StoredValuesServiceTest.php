<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use WebFramework\Entity\EntityCollection;
use WebFramework\Entity\StoredValue;
use WebFramework\Repository\StoredValueRepository;
use WebFramework\Support\StoredValuesService;

/**
 * @internal
 *
 * @covers \WebFramework\Support\StoredValuesService
 */
final class StoredValuesServiceTest extends Unit
{
    public function testGetValues()
    {
        $storedValue1 = $this->make(StoredValue::class, [
            'getName' => 'setting1',
            'getValue' => 'value1',
        ]);

        $storedValue2 = $this->make(StoredValue::class, [
            'getName' => 'setting2',
            'getValue' => 'value2',
        ]);

        $collection = new EntityCollection([$storedValue1, $storedValue2]);

        $repository = $this->make(StoredValueRepository::class, [
            'getValuesByModule' => $collection,
        ]);

        $service = new StoredValuesService($repository);
        $result = $service->getValues('testmodule');

        verify($result)->equals([
            'setting1' => 'value1',
            'setting2' => 'value2',
        ]);
    }

    public function testGetValuesEmpty()
    {
        $collection = new EntityCollection([]);

        $repository = $this->make(StoredValueRepository::class, [
            'getValuesByModule' => $collection,
        ]);

        $service = new StoredValuesService($repository);
        $result = $service->getValues('emptymodule');

        verify($result)->equals([]);
    }

    public function testGetValueExists()
    {
        $storedValue = $this->make(StoredValue::class, [
            'getValue' => Expected::once('testvalue'),
        ]);

        $repository = $this->make(StoredValueRepository::class, [
            'getValue' => Expected::once($storedValue),
        ]);

        $service = new StoredValuesService($repository);
        $result = $service->getValue('module.key');

        verify($result)->equals('testvalue');
    }

    public function testGetValueNotExists()
    {
        $repository = $this->make(StoredValueRepository::class, [
            'getValue' => Expected::once(null),
        ]);

        $service = new StoredValuesService($repository);
        $result = $service->getValue('module.key');

        verify($result)->equals('');
    }

    public function testGetValueWithDefault()
    {
        $repository = $this->make(StoredValueRepository::class, [
            'getValue' => Expected::once(null),
        ]);

        $service = new StoredValuesService($repository);
        $result = $service->getValue('module.key', 'defaultvalue');

        verify($result)->equals('defaultvalue');
    }

    public function testSetValueNew()
    {
        $repository = $this->make(StoredValueRepository::class, [
            'getValue' => Expected::once(null),
            'save' => Expected::once(),
        ]);

        $service = new StoredValuesService($repository);
        $service->setValue('module.key', 'newvalue');
    }

    public function testSetValueExisting()
    {
        $storedValue = $this->make(StoredValue::class, [
            'setValue' => Expected::once(),
        ]);

        $repository = $this->make(StoredValueRepository::class, [
            'getValue' => Expected::once($storedValue),
            'save' => Expected::once(),
        ]);

        $service = new StoredValuesService($repository);
        $service->setValue('module.key', 'updatedvalue');
    }

    public function testDeleteValueExists()
    {
        $storedValue = $this->make(StoredValue::class);

        $repository = $this->make(StoredValueRepository::class, [
            'getValue' => Expected::once($storedValue),
            'delete' => Expected::once(),
        ]);

        $service = new StoredValuesService($repository);
        $service->deleteValue('module.key');
    }

    public function testDeleteValueNotExists()
    {
        $repository = $this->make(StoredValueRepository::class, [
            'getValue' => Expected::once(null),
            'delete' => Expected::never(),
        ]);

        $service = new StoredValuesService($repository);
        $service->deleteValue('module.key');
    }

    public function testGetValueWithEmptyString()
    {
        $storedValue = $this->make(StoredValue::class, [
            'getValue' => Expected::once(''),
        ]);

        $repository = $this->make(StoredValueRepository::class, [
            'getValue' => Expected::once($storedValue),
        ]);

        $service = new StoredValuesService($repository);
        $result = $service->getValue('module.key');

        verify($result)->equals('');
    }
}
