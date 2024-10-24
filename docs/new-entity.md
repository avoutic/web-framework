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

use WebFramework\Core\EntityCore;

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

use WebFramework\Core\EntityCollection;
use WebFramework\Core\RepositoryCore;
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
        return $this->getObject(['make' => $make, 'model' => $model]);
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
        $query = <<<'SQL'
        SELECT id
        FROM cars
        WHERE make LIKE ? OR
              model LIKE ? OR
              color LIKE ?
SQL;

        $result = $this->database->query($query, [
            "%{$string}%",
            "%{$string}%",
            "%{$string}%",
        ], 'Failed to search cars');

        $data = [];
        foreach ($result as $row)
        {
            $car = $this->getObjectById($row['id']);

            if ($car === null)
            {
                throw new \RuntimeException('Failed to retrieve car');
            }

            $data[] = $car;
        }

        return new EntityCollection($data);
    }
}
~~~

### Step 3: Create the Database Table

Define a new database table `cars` that matches the structure of the `Car` entity. This is typically done through a database migration. The `DatabaseManager` class is used to manage database schema changes.

The `target_version` is used to track the version of the database schema. The `actions` array contains the actual schema changes to be applied. The actual version number is important and should be incremented with each change to the database schema. You also need to update the `versions.required_app_db` field in your config.php file to reflect the new version number.

You don't need to include a field for the `id` column in the `actions` array, as it will be added automatically.

To perform the migration you would typically run the `DbUpdateTask` task, by executing `php scripts/update_db.php` in the console of your system.

#### Example Migration File

~~~php
<?php

return [
    'target_version' => 1,
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
];
~~~