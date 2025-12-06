# Creating a new Entity

This document provides a guide for developers on how to create a new Entity class in the WebFramework. Entities are objects that represent data in the application and are persisted to the database. They should not contain any business logic, only data and methods for accessing and manipulating that data.

## Steps to Create a New Entity

1. **Define the Entity Class**: Create a new class that extends `EntityCore` and implements the necessary methods and properties.

2. **Define the Repository Class**: Create a new class that extends `RepositoryCore` and provides methods for accessing and manipulating the entity data.

3. **Create the Database Table**: Define a new database table that matches the structure of the entity.

## Example: Creating a `Car` Entity

### Step 1: Define the `Car` Entity Class

Create a new class `Car` that extends `EntityCore`. Define the properties and methods for accessing and manipulating the data.

~~~php
<?php

namespace WebFramework\Entity;

use WebFramework\Entity\EntityCore;

/**
 * Represents a car in the system.
 */
class Car extends EntityCore
{
    protected static string $tableName = 'cars';
    protected static array $baseFields = ['make', 'model', 'year', 'color'];
    protected static array $privateFields = [];

    protected int $id;
    protected string $make = '';
    protected string $model = '';
    protected int $year = 0;
    protected string $color = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function getMake(): string
    {
        return $this->make;
    }

    public function setMake(string $make): void
    {
        $this->make = $make;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): void
    {
        $this->model = $model;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): void
    {
        $this->year = $year;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): void
    {
        $this->color = $color;
    }
}
~~~

### Step 2: Define the `CarRepository` Class

Create a new class `CarRepository` that extends `RepositoryCore`. Implement methods for accessing and manipulating `Car` entities.

~~~php
<?php

namespace WebFramework\Repository;

use WebFramework\Entity\EntityCollection;
use WebFramework\Repository\RepositoryCore;
use WebFramework\Entity\Car;

/**
 * Repository class for Car entities.
 *
 * @extends RepositoryCore<Car>
 */
class CarRepository extends RepositoryCore
{
    /** @var class-string<Car> The entity class associated with this repository */
    protected static string $entityClass = Car::class;

    /**
     * Get a Car entity by make and model.
     *
     * @param string $make  The make of the car
     * @param string $model The model of the car
     *
     * @return null|Car The Car entity if found, null otherwise
     */
    public function getCarByMakeAndModel(string $make, string $model): ?Car
    {
        return $this->findOneBy(['make' => $make, 'model' => $model]);
    }

    /**
     * Search for cars based on a string.
     *
     * @param string $string The search string
     *
     * @return EntityCollection<Car> A collection of matching Car entities
     */
    public function searchCars(string $string): EntityCollection
    {
        return $this->findBy([
            'OR' => [
                'make' => ['LIKE', "%{$string}%"],
                'model' => ['LIKE', "%{$string}%"],
                'color' => ['LIKE', "%{$string}%"],
            ]
        ]);
    }
}
~~~

### Step 3: Create the Database Table

Define a new database table `cars` that matches the structure of the `Car` entity. This is typically done through a database migration. The `DatabaseManager` class is used to manage database schema changes.

Check out the [Database Migrations](./database-migrations.md) documentation for more information on how to create and apply database migrations.

The migration file uses a timestamp-based system with `up` and `down` directions. The `up` actions contain the schema changes to apply the migration, while the `down` actions contain the changes to rollback the migration. The timestamp in the filename ensures migrations are applied in chronological order, and the migrations table automatically tracks which migrations have been executed.

You don't need to include a field for the `id` column in the `actions` array, as it will be added automatically.

To perform the migration you would typically run the new migration command by executing `php Framework db:migrate` in the console of your system.

#### Example Migration File

~~~php
<?php

return [
    'up' => [
        'actions' => [
            [
                'type' => 'create_table',
                'table_name' => 'cars',
                'fields' => [
                    [
                        'name' => 'make',
                        'type' => 'varchar',
                        'size' => 255,
                        'null' => false,
                    ],
                    [
                        'name' => 'model',
                        'type' => 'varchar',
                        'size' => 255,
                        'null' => false,
                    ],
                    [
                        'name' => 'year',
                        'type' => 'int',
                        'null' => false,
                    ],
                    [
                        'name' => 'color',
                        'type' => 'varchar',
                        'size' => 255,
                        'null' => false,
                    ],
                ],
                'constraints' => [],
            ],
        ],
    ],
    'down' => [
        'actions' => [
            [
                'type' => 'raw_query',
                'query' => 'DROP TABLE IF EXISTS `cars`',
                'params' => [],
            ],
        ],
    ],
];
~~~
