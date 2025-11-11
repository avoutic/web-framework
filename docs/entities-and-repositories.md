# Entities and Repositories

This document provides a guide for developers on the Entity and Repository pattern used in the WebFramework. This pattern is used to separate the concerns of data representation and data persistence, making your application more modular and maintainable.

## Overview

In the WebFramework, entities are objects that represent data in your application. They are responsible for holding data and providing methods to access and manipulate that data. Repositories, on the other hand, are responsible for persisting entities to the database and retrieving them.

### Entities

Entities are classes that extend `EntityCore`. They define the properties and methods for accessing and manipulating data. Entities should not contain any business logic; they are purely for data representation.

### Repositories

Repositories are classes that extend `RepositoryCore`. They provide methods for storing, retrieving, and updating entities in the database. Repositories handle the database interactions, allowing entities to remain focused on data representation.

How to create new entities and repositories is described in the [New Entity and Repository Generation](./new-entity.md) document.

## Retrieving Entities with a Repository

Repositories provide several methods for retrieving entities from the database. Some methods return single entities, while others return `EntityCollection` objects.

### Single Entity Methods

These methods return either a single entity or null:

#### By ID
~~~php
<?php

$user = $userRepository->getObjectById($userId);  // Returns User|null
~~~

#### By Filter

This method takes a single filter as an array. If multiple objects are found, the function will throw an exception.

~~~php
<?php

$user = $userRepository->getObject(['email' => $email]);  // Returns User|null
~~~

### Collection Methods

These methods return an `EntityCollection` object:

#### Multiple Entities

To retrieve multiple entities, you can use the `getObjects` method. This method takes the following parameters:

- `offset`: The offset of the first entity to retrieve.
- `limit`: The number of entities to retrieve (-1 for all).
- `filter`: An array of filter conditions.
- `order`: The order by clause.

The filter is a key-value pair array where the key is the field name and the value is the value to filter by. Each key-value pair being a filter condition. The key is always a field name and the value is the value to filter by. The value can be a string, a number, boolean, null, or an array for describing an operator other than equals.

~~~php
<?php

// Returns EntityCollection<User>
$users = $userRepository->getObjects(0, 10, [
        'email' => $email,
        'active' => true,
        'last_login' => [ '>', strtotime('-1 month') ],
    ],
    'order' => 'username ASC',
);
~~~

### Counting Entities matching a filter

You can count the number of entities matching a filter using the `countObjects` method. This method takes a single filter as an array.

#### Custom Query
~~~php
<?php

// Returns EntityCollection<User>
$query = 'SELECT * FROM users WHERE last_login > ?';
$params = [strtotime('-1 month')];
$users = $userRepository->getFromQuery($query, $params);
~~~

## Working with EntityCollection

`EntityCollection` is a specialized class for handling collections of entities. It implements both `Iterator` and `Countable` interfaces, providing several advantages over regular arrays:

### Key Features
- Type-safe iteration over entities
- Built-in counting functionality
- Methods for bulk operations
- Easy conversion to arrays

### Example Usage

#### Iterating Over a Collection
~~~php
<?php

$users = $userRepository->getObjects(0, 10);
foreach ($users as $user) {
    echo $user->getUsername();
}
~~~

#### Counting Entities
~~~php
<?php

$userCount = $users->count();
~~~

#### Converting to Array
You can convert an EntityCollection to an array in two ways:

1. Convert to an array of entity arrays:
~~~php
<?php

$arrayOfArrays = $users->toArray();  // Each entity is converted to array form
~~~

2. Apply a custom callback to each entity:
~~~php
<?php

$usernames = $users->call(function($user) {
    return $user->getUsername();
});
~~~

### EntityCollection vs Array

Here's why EntityCollection is preferred over regular arrays:

1. **Type Safety**: EntityCollection is generic-typed, ensuring all items are of the same entity type
2. **Iteration Control**: Provides controlled iteration without exposing the underlying array
3. **Bulk Operations**: Built-in methods for operating on all entities at once
4. **Memory Efficiency**: Lazy loading capabilities can be implemented without changing the interface
5. **Consistency**: Ensures consistent behavior across the application

## Example: Updating Multiple Entities

Here's an example of working with an EntityCollection:

~~~php
<?php

use App\Repository\UserRepository;

class UserService
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    public function deactivateInactiveUsers(int $daysInactive): void
    {
        $query = 'SELECT * FROM users WHERE last_login < ?';
        $params = [strtotime("-{$daysInactive} days")];
        
        $inactiveUsers = $this->userRepository->getFromQuery($query, $params);
        
        foreach ($inactiveUsers as $user) {
            $user->setActive(false);
            $this->userRepository->save($user);
        }
        
        // Or get a count of affected users
        $affectedCount = $inactiveUsers->count();
    }
}
~~~