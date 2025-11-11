# Database Usage

This document provides a guide for developers on how to use the database in the WebFramework. It covers the basic operations, including executing queries, inserting data, and managing transactions.

In most cases, you should use [Entities and Repositories](./entities-and-repositories.md) to interact with the database. But there are multiple cases where you might want to interact with the database directly.

## Overview

The WebFramework uses the `Database` interface to define the contract for database operations. The default implementation is `MysqliDatabase`, which uses the MySQLi extension to interact with a MySQL database.

Most interactions with the database are done via Entity Repositories. But there are multiple cases where you might want to interact with the database directly.

## Executing Queries

To execute a query, you use the `query()` method. This method is used for executing SELECT, UPDATE, DELETE, and other non-insert queries.

### Example: Executing a Query

~~~php
<?php

use WebFramework\Database\Database;

class ExampleService
{
    public function __construct(
        private Database $database,
    ) {}

    public function listActiveUsers(): void
    {
        $query = 'SELECT * FROM users WHERE active = ?';
        $params = [1];

        $result = $this->database->query($query, $params);

        foreach ($result as $row) {
            echo $row['username'] . ' - ' . $row['email'] . PHP_EOL;
        }
    }
}
~~~

In this example, the `ExampleService` uses the `query()` method to retrieve all active users from the `users` table.

## Inserting Data

To insert data into the database, you use the `insertQuery()` method. This method executes an INSERT query and returns the ID of the last inserted row.

### Example: Inserting Data

~~~php
<?php

use WebFramework\Database\Database;

class UserService
{
    public function __construct(
        private Database $database,
    ) {}

    public function addUser(string $username, string $email): int
    {
        $query = 'INSERT INTO users (username, email) VALUES (?, ?)';
        $params = [$username, $email];

        return $this->database->insertQuery($query, $params);
    }
}
~~~

In this example, the `UserService` uses the `insertQuery()` method to add a new user to the `users` table and returns the new user's ID.

## Managing Transactions

Transactions are used to ensure that a series of database operations are executed atomically. You can start a transaction using the `startTransaction()` method and commit it using the `commitTransaction()` method.

### Example: Using Transactions

~~~php
<?php

use WebFramework\Database\Database;

class OrderService
{
    public function __construct(
        private Database $database,
    ) {}

    public function placeOrder(array $orderData): void
    {
        $this->database->startTransaction();

        try {
            // Insert order
            $orderId = $this->database->insertQuery('INSERT INTO orders (customer_id, total) VALUES (?, ?)', [
                $orderData['customer_id'],
                $orderData['total'],
            ]);

            // Insert order items
            foreach ($orderData['items'] as $item) {
                $this->database->insertQuery('INSERT INTO order_items (order_id, product_id, quantity) VALUES (?, ?, ?)', [
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                ]);
            }

            // Commit transaction
            $this->database->commitTransaction();
        } catch (\Exception $e) {
            // Rollback transaction on error
            $this->database->rollbackTransaction();
            throw $e;
        }
    }
}
~~~

In this example, the `OrderService` uses a transaction to ensure that both the order and its items are inserted into the database atomically. If an error occurs, the transaction is rolled back.