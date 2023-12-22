<?php

declare(strict_types=1);

namespace WebFramework\PHPStan;

use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;

class EntityIdPropertiesExtension implements ReadWritePropertiesExtension
{
    public function isAlwaysRead(PropertyReflection $property, string $propertyName): bool
    {
        return false;
    }

    public function isAlwaysWritten(PropertyReflection $property, string $propertyName): bool
    {
        $class = $property->getDeclaringClass();
        $className = $class->getName();

        if (!str_contains($className, '\\Entity\\'))
        {
            return false;
        }

        return ($propertyName === 'id');
    }

    public function isInitialized(PropertyReflection $property, string $propertyName): bool
    {
        return false;
    }
}
