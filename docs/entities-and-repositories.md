# Entities and Repositories

This document provides a guide for developers on the Entity and Repository pattern used in the WebFramework. This pattern is used to separate the concerns of data representation and data persistence, making your application more modular and maintainable.

## Overview

In the WebFramework, entities are objects that represent data in your application. They are responsible for holding data and providing methods to access and manipulate that data. Repositories, on the other hand, are responsible for persisting entities to the database and retrieving them.

### Entities

Entities are classes that extend `EntityCore`. They define the properties and methods for accessing and manipulating data. Entities should not contain any business logic; they are purely for data representation.

### Repositories

Repositories are classes that extend `RepositoryCore`. They provide methods for storing, retrieving, and updating entities in the database. Repositories handle the database interactions, allowing entities to remain focused on data representation.

How to create new entities and repositories is described in the [New Entity and Repository Generation](./new-entity.md) document.


### Fluent Query Builder

Repositories provide a fluent query builder that allows you to construct complex queries without writing SQL. The query builder supports filtering, ordering, limiting, and executing the query.

The query builder is built on top of the `RepositoryQuery` class.

## Retrieving Entities with a Repository

Repositories provide several methods for retrieving entities from the database. Some methods return single entities, while others return `EntityCollection` objects.

### Single Entity by ID
~~~php
<?php

$user = $userRepository->find($userId);  // Returns User|null

// Similar to the above, but using the fluent query builder
$user = $userRepository
    ->query(['id' => $userId])
    ->getOne();
~~~

### Single Entity by filter

This method takes a single filter as an array. If multiple objects are found, the function will throw an exception.

~~~php
<?php

$user = $userRepository->findOneBy(['email' => $email]);  // Returns User|null

// Similar to the above, but using the fluent query builder
$user = $userRepository
    ->query(['email' => $email])
    ->getOne();
~~~

### Multiple Entities by filter

To retrieve multiple entities, you can use the `getObjects` method. This method takes the following parameters:

- `offset`: The offset of the first entity to retrieve.
- `limit`: The number of entities to retrieve (-1 for all).
- `filter`: An array of filter conditions.
- `order`: The order by clause.

The filter is a key-value pair array where the key is the field name and the value is the value to filter by. Each key-value pair being a filter condition. The key is always a field name and the value is the value to filter by. The value can be a string, a number, boolean, null, or an array for describing an operator other than equals.

### Basic Filtering

~~~php
<?php

// Returns EntityCollection<User>
$users = $userRepository->findBy([
        'email' => $email,
        'active' => true,
        'last_login' => [ '>', strtotime('-1 month') ],
    ],
    'username ASC',
    10,
    0,
);

// Similar to the above, but using the fluent query builder
$users = $userRepository
    ->query([
        'email' => $email,
        'active' => true,
        'last_login' => [ '>', strtotime('-1 month') ]
    ])
    ->orderBy('username ASC')
    ->limit(10)
    ->execute();
~~~

### Advanced Filtering

The repository supports advanced filtering options including OR conditions, column comparisons, and nested logic. To start a fluent query builder, you can use the `query` method, which takes an optional array of filter conditions (similar to the `where()` method).

#### Fluent Where Helpers
In addition to the array-based `where` method, several fluent helpers are available for common conditions:

~~~php
<?php

$users = $userRepository->query()
    ->whereIn('role', ['admin', 'manager'])
    ->whereNotIn('status', ['banned', 'deleted'])
    ->whereNull('deleted_at')
    ->whereNotNull('verified_at')
    ->whereBetween('age', 18, 65)
    ->whereNotBetween('score', 0, 10)
    ->whereLike('username', 'admin%')
    ->whereNotLike('email', '%@temp.com')
    ->execute();
~~~

#### OR Conditions
You can use the `OR` key to create (nested) OR conditions:

~~~php
<?php

$users = $userRepository->findBy([
        'active' => true,
        'OR' => [
            ['role' => 'admin'],
            ['role' => 'manager']
        ]
    ]);
// Result: active = 1 AND (role = 'admin' OR role = 'manager')
~~~

#### Column Comparison
You can compare a field against another column using the `Column` class:

~~~php
<?php

use WebFramework\Repository\Column;

$jobs = $jobRepository->findBy([
    'attempts' => ['<', new Column('max_attempts')]
]);
// Result: attempts < max_attempts
~~~

#### Conditional Clauses
You can conditionally add clauses to the query using the `when` method. This is useful for building queries based on dynamic input without breaking the fluent chain.

~~~php
<?php

$repository
    ->query(['active' => true])
    ->when(
        $searchQuery,
        fn ($query) => $query->whereLike('name', "%{$searchQuery}%"),
    )
    ->when(
        $sortBy,
        fn ($query) => $query->orderBy($sortBy),
        fn ($query) => $query->orderBy('created_at'),
    )
    ->execute();
~~~

#### Multiple Conditions per Field
You can apply multiple conditions to a single field by passing an array of conditions:

~~~php
<?php

$items = $repository->findBy([
    'reserved_at' => [
        ['!=', null],
        ['<', $time]
    ]
]);
// Result: reserved_at IS NOT NULL AND reserved_at < ?
~~~

#### OR Conditions on a Single Field
You can also use OR logic within a single field definition:

~~~php
<?php

$items = $repository->findBy([
    'status' => [
        'OR' => [
            'pending',
            'failed'
        ]
    ]
]);
// Result: (status = 'pending' OR status = 'failed')

// Or with complex conditions:
$items = $repository->findBy([
    'reserved_at' => [
        'OR' => [
            null,
            ['<', $time]
        ]
    ]
]);
// Result: (reserved_at IS NULL OR reserved_at < ?)
~~~

#### Ordering
You can order the results by a column using the `orderBy` method.

~~~php
<?php

$users = $userRepository->query()->orderBy('username ASC')->execute();
~~~

or using the `orderByAsc` and `orderByDesc` methods:

~~~php
<?php

$users = $userRepository->query()->orderByAsc('username')->execute();
~~~

or using the `inRandomOrder` method:

~~~php
<?php

$users = $userRepository->query()->inRandomOrder()->execute();
~~~

### Custom Queries retrieving a collection of Entities

If you have a more complex query, that still retrieves just a collection of a single entity type, you can use the `getFromQuery` method. This method takes a SQL query and an array of parameters.

~~~php
<?php

// Returns EntityCollection<User> with users that have the role with id 1
$query = <<<SQL
SELECT u.*
FROM users AS u
WHERE u.id IN (
    SELECT user_id
    FROM user_roles
    WHERE role_id = ?
)
ORDER BY u.username ASC
SQL;

$params = [1];
$users = $userRepository->getFromQuery($query, $params);
~~~

### Custom Queries retrieving more than one Entity type

Sometimes you are able to retrieve two types of entities from a single query. For example, you might want to retrieve a user and their roles from a single query.

~~~php
<?php

$userSelect = $this->userRepository->getAliasedFields('u');
$roleSelect = $this->roleRepository->getAliasedFields('r');

$selectFields = implode(', ', array_merge($userSelect, $roleSelect));

$query = <<<SQL
SELECT {$selectFields}
FROM users AS u
LEFT JOIN user_roles AS r ON u.id = r.user_id
ORDER BY u.username ASC
SQL;

$result = $this->database->query($query, [], 'Failed to retrieve users and roles');

foreach ($result as $row) {
    // Now retrieve each pair of user and role
    $user = $this->userRepository->instantiateEntityFromData($row, 'u');
    $role = $this->roleRepository->instantiateEntityFromData($row, 'r');
    // Do something with the user and role
}
~~~

### Pagination

The query builder provides a `paginate` method to paginate results. This method returns a `Paginator` object.

~~~php
<?php

// Get page 1 with 20 items per page
$paginator = $userRepository
    ->query(['active' => true])
    ->orderBy('created_at DESC')
    ->paginate(20, 1);

$users = $paginator->getItems(); // EntityCollection
$total = $paginator->getTotal();
$currentPage = $paginator->getCurrentPage();
$lastPage = $paginator->getLastPage();
~~~

### Chunking

If you need to process a large number of entities, you can use the `chunk` method to retrieve a small number of results at a time. This reduces memory usage by not loading all records into memory at once.

~~~php
<?php

$userRepository
    ->query(['active' => true])
    ->chunk(100, function(EntityCollection $users) {
        foreach ($users as $user) {
            // Process user
        }

        // Return false to stop processing if needed
        // return false;
    });
~~~

### Plucking Values

If you only need to retrieve a single column's value (or a key-value pair) from the database, you can use the `pluck` method.

~~~php
<?php

// Returns array of emails: ['user1@example.com', 'user2@example.com', ...]
$emails = $userRepository
    ->query(['active' => true])
    ->pluck('email');

// Returns associative array where id is key and username is value: [1 => 'user1', 2 => 'user2', ...]
$usernames = $userRepository
    ->query(['active' => true])
    ->pluck('username', 'id');
~~~

### Retrieving the First Result

To retrieve the first result of a query, you can use the `first` method. This is equivalent to calling `limit(1)->getOne()`.

~~~php
<?php

$latestUser = $userRepository
    ->query(['active' => true])
    ->orderBy('created_at DESC')
    ->first();
~~~

To retrieve the first result or throw an exception if not found:

~~~php
<?php

$user = $userRepository
    ->query(['id' => 1])
    ->firstOrFail(); // Throws RuntimeException if not found
~~~

### Finding by ID

The `find` method is a shortcut for retrieving an entity by its primary key:

~~~php
<?php

$user = $userRepository->find(1);
~~~

or using the `findOrFail` method, which throws a `RuntimeException` if the entity is not found:

~~~php
<?php

$user = $userRepository->findOrFail(1);
// Throws RuntimeException if not found
~~~

### Retrieving a Single Value

If you only need a single scalar value from the first result:

~~~php
<?php

$email = $userRepository
    ->query(['id' => 1])
    ->value('email');
// Returns "user@example.com" or null
~~~

### Selecting Specific Columns

To select only specific columns instead of the full entity (returns an array of arrays):

~~~php
<?php

// Returns: [['id' => 1, 'username' => '...', 'email' => '...'], ['id' => 2, 'username' => '...', 'email' => '...']]
$users = $userRepository
    ->query(['active' => true])
    ->select(['id', 'username', 'email'])
    ->execute();
~~~

To retrieve only the values of the first result:

~~~php
<?php

// Returns: ['id' => 1, 'username' => '...', 'email' => '...']
$user = $userRepository
    ->query(['id' => 1])
    ->selectOne(['id', 'username', 'email']);
~~~

### Distinct Results

To retrieve unique results:

~~~php
<?php

$statuses = $userRepository
    ->query()
    ->distinct()
    ->pluck('status');
~~~

### Grouping Results

You can group results by one or more columns using the `groupBy` method. This is often used in conjunction with aggregate functions.

~~~php
<?php

// Using aggregates with grouping
$totalCreditsByStatus = $userRepository
    ->query()
    ->groupBy('status')
    ->sum('credits');
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

$users = $userRepository
        ->query()
        ->limit(10)
        ->execute()
;

foreach ($users as $user) {
    echo $user->getUsername();
}
~~~

#### Counting Entities
~~~php
<?php

$userCount = $users->count();
~~~

Or to count distinct values in a column:

~~~php
<?php

$userCount = $this->userRepository
    ->query()
    ->distinct()
    ->count('username');
// Returns the number of unique usernames
~~~

#### Converting to Array
You can convert an EntityCollection to an array in two ways:

1. Convert to an array of Entities:
~~~php
<?php

$arrayOfEntities = $users->getEntities();  // Each entity is returned as an object
~~~

2. Convert to an array of entity arrays:
~~~php
<?php

$arrayOfArrays = $users->toArray();  // Each entity is converted to array form
~~~

3. Apply a custom callback to each entity:
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
        $inactiveUsers = $this->userRepository
            ->query([
                'last_login' => [ '<', strtotime("-{$daysInactive} days") ],
            ])
            ->execute()
        ;
        
        foreach ($inactiveUsers as $user) {
            $user->setActive(false);
            $this->userRepository->save($user);
        }
        
        // Or get a count of affected users
        $affectedCount = $inactiveUsers->count();
    }
}
~~~

## Batch Operations

The fluent query builder allows you to perform update and delete operations directly on the database without retrieving the entities first. This is more efficient for bulk operations.

### Batch Update

To update multiple records at once:

~~~php
<?php

$affectedRows = $userRepository
    ->query([
        'active' => true,
        'last_login' => [ '<', strtotime('-1 year') ],
    ])
    ->update(['active' => false]);
~~~

### Batch Delete

To delete multiple records at once:

~~~php
<?php

$affectedRows = $userRepository
    ->query([
        'active' => false,
    ])
    ->delete();
~~~

## Debugging

You can inspect the generated SQL and parameters using the following methods:

~~~php
<?php

// Get SQL string and parameters
[$sql, $params] = $userRepository
    ->query(['active' => true])
    ->toSql();

// Get UPDATE SQL
[$sql, $params] = $userRepository
    ->query(['id' => 1])
    ->toUpdateSql(['active' => false]);

// Get DELETE SQL
[$sql, $params] = $userRepository
    ->query(['id' => 1])
    ->toDeleteSql();
~~~
