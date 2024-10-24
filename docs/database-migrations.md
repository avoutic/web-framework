# Database Migrations

This document lists all the actions supported by the DatabaseManager class in WebFramework. For each action, you'll find a description, required and optional fields, and an example db_scheme migration file.

## Create Table

Creates a new table in the database.

### Fields
- `type` (required): Must be "create_table"
- `table_name` (required): The name of the table to create
- `fields` (required): An array of field definitions
- `constraints` (required): An array of constraint definitions

### Field Definition
Each field definition is an associative array that can include the following keys:
- `name` (required): The name of the field
- `type` (required): The data type of the field (e.g., 'varchar', 'int', 'datetime', 'text')
- `size` (optional): The size or length of the field (for types that support it, like varchar)
- `null` (optional): Boolean indicating whether the field can be null (default is false)
- `default` (optional): The default value for the field
- `after` (optional): The name of the field after which this field should be added
- `foreign_table` (required for foreign keys): The name of the table this foreign key references
- `foreign_field` (required for foreign keys): The name of the field in the foreign table this key references
- `on_delete` (optional for foreign keys): The action to take when the referenced row is deleted
- `on_update` (optional for foreign keys): The action to take when the referenced row is updated

Note that you don't need to include a field for the `id` column in the `actions` array, as it will be added automatically.

### Constraint Definition
Each constraint definition is an associative array that can include the following keys:
- `type` (required): The type of constraint (e.g., 'unique')
- `values` (required for 'unique' type): An array of field names that should be unique together

### Example

~~~php
<?php

return [
    'target_version' => 1,
    'actions' => [
        [
            'type' => 'create_table',
            'table_name' => 'users',
            'fields' => [
                [
                    'name' => 'username',
                    'type' => 'varchar',
                    'size' => 255,
                    'null' => false,
                ],
                [
                    'name' => 'email',
                    'type' => 'varchar',
                    'size' => 255,
                    'null' => false,
                ],
                [
                    'name' => 'created_at',
                    'type' => 'datetime',
                    'null' => false,
                    'default' => 'CURRENT_TIMESTAMP',
                ],
                [
                    'name' => 'role_id',
                    'type' => 'foreign_key',
                    'foreign_table' => 'roles',
                    'foreign_field' => 'id',
                    'on_delete' => 'CASCADE',
                    'on_update' => 'RESTRICT',
                ],
            ],
            'constraints' => [
                [
                    'type' => 'unique',
                    'values' => ['username'],
                ],
                [
                    'type' => 'unique',
                    'values' => ['email'],
                ],
            ],
        ],
    ],
];
~~~

## Create Trigger

Creates a new trigger in the database.

### Fields
- `type` (required): Must be "create_trigger"
- `table_name` (required): The name of the table for the trigger
- `trigger` (required): An array containing trigger details
  - `name` (required): The name of the trigger
  - `time` (required): The trigger time (BEFORE or AFTER)
  - `event` (required): The trigger event (INSERT, UPDATE, or DELETE)
  - `action` (required): The SQL statement to execute when the trigger fires

### Example

~~~php
<?php

return [
    'target_version' => 2,
    'actions' => [
        [
            'type' => 'create_trigger',
            'table_name' => 'users',
            'trigger' => [
                'name' => 'update_last_modified',
                'time' => 'BEFORE',
                'event' => 'UPDATE',
                'action' => 'SET NEW.updated_at = CURRENT_TIMESTAMP',
            ],
        ],
    ],
];
~~~

## Add Column

Adds a new column to an existing table.

### Fields
- `type` (required): Must be "add_column"
- `table_name` (required): The name of the table to alter
- `field` (required): An array containing the new column details

### Example

~~~php
<?php

return [
    'target_version' => 3,
    'actions' => [
        [
            'type' => 'add_column',
            'table_name' => 'users',
            'field' => [
                'name' => 'last_login',
                'type' => 'datetime',
                'null' => true,
            ],
        ],
    ],
];
~~~

## Add Constraint

Adds a new constraint to an existing table.

### Fields
- `type` (required): Must be "add_constraint"
- `table_name` (required): The name of the table to alter
- `constraint` (required): An array containing the constraint details

### Example

~~~php
<?php

return [
    'target_version' => 4,
    'actions' => [
        [
            'type' => 'add_constraint',
            'table_name' => 'users',
            'constraint' => [
                'type' => 'unique',
                'values' => ['username', 'email'],
            ],
        ],
    ],
];
~~~

## Insert Row

Inserts a new row into a table.

### Fields
- `type` (required): Must be "insert_row"
- `table_name` (required): The name of the table to insert into
- `values` (required): An array of column-value pairs to insert

### Example

~~~php
<?php

return [
    'target_version' => 5,
    'actions' => [
        [
            'type' => 'insert_row',
            'table_name' => 'users',
            'values' => [
                'username' => 'admin',
                'email' => 'admin@example.com',
                'created_at' => '2023-04-01 00:00:00',
            ],
        ],
    ],
];
~~~

## Modify Column Type

Modifies the type or attributes of an existing column.

### Fields
- `type` (required): Must be "modify_column_type"
- `table_name` (required): The name of the table to alter
- `field` (required): An array containing the modified column details

### Example

~~~php
<?php

return [
    'target_version' => 6,
    'actions' => [
        [
            'type' => 'modify_column_type',
            'table_name' => 'users',
            'field' => [
                'name' => 'username',
                'type' => 'varchar',
                'size' => 100,
                'null' => false,
            ],
        ],
    ],
];
~~~

## Rename Column

Renames an existing column in a table.

### Fields
- `type` (required): Must be "rename_column"
- `table_name` (required): The name of the table to alter
- `name` (required): The current name of the column
- `new_name` (required): The new name for the column

### Example

~~~php
<?php

return [
    'target_version' => 7,
    'actions' => [
        [
            'type' => 'rename_column',
            'table_name' => 'users',
            'name' => 'email',
            'new_name' => 'user_email',
        ],
    ],
];
~~~

## Rename Table

Renames an existing table.

### Fields
- `type` (required): Must be "rename_table"
- `table_name` (required): The current name of the table
- `new_name` (required): The new name for the table

### Example

~~~php
<?php

return [
    'target_version' => 8,
    'actions' => [
        [
            'type' => 'rename_table',
            'table_name' => 'users',
            'new_name' => 'app_users',
        ],
    ],
];
~~~

## Raw Query

Executes a raw SQL query.

### Fields
- `type` (required): Must be "raw_query"
- `query` (required): The raw SQL query to execute
- `params` (required): An array of parameters for the query (can be empty)

### Example

~~~php
<?php

return [
    'target_version' => 9,
    'actions' => [
        [
            'type' => 'raw_query',
            'query' => 'UPDATE users SET status = ? WHERE last_login < ?',
            'params' => ['inactive', '2023-01-01 00:00:00'],
        ],
    ],
];
~~~

## Run Task

Executes a custom task defined in a separate class.

### Fields
- `type` (required): Must be "run_task"
- `task` (required): The fully qualified class name of the task to run

### Example

~~~php
<?php

return [
    'target_version' => 10,
    'actions' => [
        [
            'type' => 'run_task',
            'task' => 'App\Tasks\CustomMigrationTask',
        ],
    ],
];
~~~

Note: The custom task class must implement the `TaskInterface` and be registered in the dependency injection container.
