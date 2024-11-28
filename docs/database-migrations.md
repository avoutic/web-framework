# Database Migrations

This document describes how to manage database migrations in WebFramework, including file organization, versioning, and the actions supported by the DatabaseManager class.

## Migration File Organization

Database migration files should be placed in the `db_scheme` directory of your application. Each migration file should be numbered incrementally and have a `.php` extension. For example:

~~~
db_scheme/
├── 1.php  # Initial schema
├── 2.php  # Add user roles
├── 3.php  # Add email verification
└── 4.php  # Add user preferences
~~~

### File Naming

- Files should be numbered incrementally (1.php, 2.php, 3.php, etc.)
- Each number represents a database version
- Files must be applied in order
- No gaps in numbering are allowed

### Version Management

After adding a new migration file, you must update the required database version in your `config/config.php`:

~~~php
return [
    // Other configuration settings...
    'versions' => [
        'required_app_db' => 4,  // Should match your latest migration file number
    ],
];
~~~

This ensures that the application knows which database version it requires to function correctly.

## Migration Actions

The DatabaseManager supports various actions for modifying the database schema. Here are all the supported actions:

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
- `type` (required): The data type of the field. Common types include:
  - String types (require quotes for default values):
    - `varchar`: Variable-length string (requires size)
    - `char`: Fixed-length string (requires size)
    - `text`: Text field
    - `tinytext`: Small text field
    - `mediumtext`: Medium text field
    - `longtext`: Large text field
    - `enum`: Enumerated values
    - `set`: Set of values
    - `json`: JSON data
  - Binary types (require quotes for default values):
    - `binary`: Fixed-length binary (requires size)
    - `varbinary`: Variable-length binary (requires size)
    - `blob`: Binary large object
    - `tinyblob`: Small binary large object
    - `mediumblob`: Medium binary large object
    - `longblob`: Large binary large object
  - Date/Time types (require quotes for default values unless using functions):
    - `datetime`: Date and time
    - `timestamp`: Timestamp
    - `date`: Date
    - `time`: Time
  - Numeric types (no quotes for default values):
    - `int`: Integer (optional size)
    - `tinyint`: Small integer (optional size)
    - `smallint`: Small integer (optional size)
    - `mediumint`: Medium integer (optional size)
    - `bigint`: Large integer (optional size)
    - `decimal`: Decimal number (optional size)
    - `numeric`: Numeric value (optional size)
    - `float`: Floating point number
    - `double`: Double precision number
  - Special types:
    - `foreign_key`: Foreign key reference
- `size` (required/optional): The size or length of the field:
  - Required for: varchar, char, binary, varbinary
  - Optional for: int, tinyint, smallint, mediumint, bigint, decimal, numeric
  - Not used for: text, datetime, timestamp, date, time, json
- `default` (optional): The default value for the field. Can be either:
  - A string or number value: Will be quoted automatically based on the field type
  - An array with a 'function' key: The value will be used as-is without quotes as a SQL function

### Example with Various Types and Defaults

~~~php
<?php

return [
    'target_version' => 1,
    'actions' => [
        [
            'type' => 'create_table',
            'table_name' => 'items',
            'fields' => [
                [
                    'name' => 'name',
                    'type' => 'varchar',
                    'size' => 255,
                    'default' => 'Unnamed Item',  // Will be: DEFAULT 'Unnamed Item'
                ],
                [
                    'name' => 'created_at',
                    'type' => 'datetime',
                    'default' => ['function' => 'CURRENT_TIMESTAMP'],  // Will be: DEFAULT CURRENT_TIMESTAMP
                ],
                [
                    'name' => 'status',
                    'type' => 'enum',
                    'default' => 'pending',  // Will be: DEFAULT 'pending'
                ],
                [
                    'name' => 'count',
                    'type' => 'int',
                    'default' => 0,  // Will be: DEFAULT 0
                ],
                [
                    'name' => 'metadata',
                    'type' => 'json',
                    'default' => '{}',  // Will be: DEFAULT '{}'
                ],
                [
                    'name' => 'type_id',
                    'type' => 'foreign_key',
                    'foreign_table' => 'item_types',
                    'foreign_field' => 'id',
                    'on_delete' => 'SET NULL',
                    'on_update' => 'CASCADE',
                ],
            ],
            'constraints' => [
                [
                    'type' => 'unique',
                    'values' => ['name'],
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
