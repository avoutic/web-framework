# Entity and Repository Pattern

This document provides a guide for developers on the Entity and Repository pattern used in the WebFramework. This pattern is used to separate the concerns of data representation and data persistence, making your application more modular and maintainable.

## Overview

In the WebFramework, entities are objects that represent data in your application. They are responsible for holding data and providing methods to access and manipulate that data. Repositories, on the other hand, are responsible for persisting entities to the database and retrieving them.

### Entities

Entities are classes that extend `EntityCore`. They define the properties and methods for accessing and manipulating data. Entities should not contain any business logic; they are purely for data representation.

### Repositories

Repositories are classes that extend `RepositoryCore`. They provide methods for storing, retrieving, and updating entities in the database. Repositories handle the database interactions, allowing entities to remain focused on data representation.

How to create new entities and repositories is described in the [New Entity and Repository Generation](./new-entity.md) document.

## Retrieving Entities with a Repository

Repositories provide several methods for retrieving entities from the database. Here are some common ways to retrieve entities:

### By ID

You can retrieve an entity by its ID using the `getObjectById` method.

~~~php
$user = $userRepository->getObjectById($userId);
~~~

### By Filter

You can retrieve an entity using a filter with the `getObject` method. This method allows you to specify conditions for the query.

~~~php
$user = $userRepository->getObject(['email' => $email]);
~~~

### Multiple Entities

To retrieve multiple entities, use the `getObjects` method. You can specify filters, ordering, and pagination.

~~~php
$users = $userRepository->getObjects(0, 10, ['active' => 1], 'username ASC');
~~~

### Custom Query

For more complex queries, you can use the `getFromQuery` method to execute a custom SQL query.

~~~php
$query = 'SELECT * FROM users WHERE last_login > ?';
$params = [strtotime('-1 month')];
$users = $userRepository->getFromQuery($query, $params);
~~~

## Example: Updating an Object and Saving to the Database

Here's an example of how to update an entity and save the changes to the database using a repository.

~~~php
use App\Repository\UserRepository;

class UserService
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    public function updateUserEmail(User $user, string $newEmail): void
    {
        $user->setEmail($newEmail);
        $this->userRepository->save($user);
    }
}
~~~

In this example, the `UserService` class updates the email address of a user and then saves the changes back to the database using the `save` method of the repository.
