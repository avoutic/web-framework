<?php

declare(strict_types=1);

namespace WebFramework\PHPStan;

use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use WebFramework\Entity\Entity;

/**
 * PHPStan extension that knows the id property of an entity is written by the repository.
 *
 * Without it, PHPStan reports the id of every entity as never written.
 */
class EntityIdPropertiesExtension implements ReadWritePropertiesExtension
{
    public function isAlwaysRead(PropertyReflection $property, string $propertyName): bool
    {
        return false;
    }

    public function isAlwaysWritten(PropertyReflection $property, string $propertyName): bool
    {
        if ($propertyName !== 'id')
        {
            return false;
        }

        // The id of an entity is set by the repository, when the entity is loaded or
        // stored. Recognize entities by their type, wherever they live
        //
        $class = $property->getDeclaringClass();

        return $class->implementsInterface(Entity::class);
    }

    public function isInitialized(PropertyReflection $property, string $propertyName): bool
    {
        return false;
    }
}
