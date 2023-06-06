<?php declare(strict_types = 1);

namespace WebFramework\PHPStan;

use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;

class EntityPropertiesExtension implements ReadWritePropertiesExtension
{
	public function isAlwaysRead(PropertyReflection $property, string $propertyName): bool
	{
        return $this->internalCheck($property, $propertyName);
    }

	public function isAlwaysWritten(PropertyReflection $property, string $propertyName): bool
	{
        return $this->internalCheck($property, $propertyName);
    }

	public function isInitialized(PropertyReflection $property, string $propertyName): bool
	{
        return $this->internalCheck($property, $propertyName);
    }

    private function internalCheck(PropertyReflection $property, string $propertyName): bool
    {
		$class = $property->getDeclaringClass();
        $className = $class->getName();

        if (strpos($className, '\\Entity\\') === false)
        {
            return false;
        }

        if (!$class->hasNativeProperty('baseFields'))
        {
            return false;
        }

        if ($propertyName === 'id')
        {
            return true;
        }

        if ($propertyName === 'unknown')
        {
            return false;
        }

        $baseFieldsProperty = $class->getNativeProperty('baseFields')->getNativeReflection();

        $baseFields = $baseFieldsProperty->getValue();
        $snakeName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $propertyName));

        return in_array($snakeName, $baseFields);
	}
}
