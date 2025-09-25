# Database Migrations

This document describes how to manage database migrations in WebFramework using the new timestamp-based migration system introduced in WebFramework v9.

## Migration File Organization

Database migration files should be placed in the `migrations` directory of your application. Each migration file uses a timestamp-based naming convention and has a `.php` extension. For example:

~~~
migrations/
├── 2025_06_10_120000_create_products.php
├── 2025_06_10_130000_add_product_code.php
├── 2025_06_10_140000_add_email_verification.php
└── 2025_06_10_150000_add_newsletter_preference.php
~~~

### File Naming Convention

Migration files follow the format: `YYYY_MM_DD_HHMMSS_description.php`

- `YYYY_MM_DD_HHMMSS`: Timestamp when the migration was created
- `description`: Brief description of what the migration does (snake_case)
- Files are executed in chronological order based on the timestamp

### Migration Tracking

The system uses a `migrations` table to track which migrations have been executed, replacing the old version-based system. This table automatically tracks:

- Migration filename
- Migration type (framework or app)
- Batch number (for rollback grouping)
- Execution timestamp

No manual version configuration is required in your config files.

### Migration Structure

Each migration file contains both `up` and `down` directions for forward and rollback operations:

~~~php
<?php

return [
    'up' => [
        'actions' => [
            // Actions to apply the migration
        ],
    ],
    'down' => [
        'actions' => [
            // Actions to rollback the migration
        ],
    ],
];
~~~

## CLI Commands

The new migration system provides several CLI commands for managing migrations:

### Run Migrations

Execute all pending migrations:

~~~bash
php Framework db:migrate
~~~

Options:
- `--dry-run` or `-d`: Preview what would be executed without making changes
- `--framework` or `-f`: Run only framework migrations

### Check Migration Status

View the status of all migrations:

~~~bash
php Framework db:status
~~~

This shows executed and pending migrations for both framework and application code.

### Generate New Migration

Create a new migration file with the correct timestamp format:

~~~bash
php Framework db:make create_products_table
~~~

This generates a file like `2025_06_10_120000_create_products_table.php` with the basic up/down structure.

### Migrate from Legacy System

If upgrading from the old numbered migration system, use:

~~~bash
php Framework db:migrate-from-scheme
~~~

This registers existing migrations as "already executed" to prevent re-application.

## Migration from Legacy System

If you're upgrading from the old `db_scheme` numbered migration system:

1. **Run the migration command as a developer**: `php Framework db:convert-from-scheme`
2. **Remove old system**: Move `db_scheme` to `db_scheme_old` or remove it entirely
3. **Remove config**: Delete `versions.required_app_db` from your config
4. **Verify database**: Ensure all required tables exist and are correct. This conversion assumes you had WebFramework 8 compatible tables before running the command.

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
- `null` (optional): If a field can be NULL (default: false)
- `default` (optional): The default value for the field. Can be either:
  - A string or number value: Will be quoted automatically based on the field type
  - An array with a 'function' key: The value will be used as-is without quotes as a SQL function

### Constraint Definition

Each constraint definition is an associative array that can include the following keys:

- `type` (required): The type of constraint. Common types include:
  - `unique`: Unique constraint
  - `index`: Index constraint
- `name` (optional): The name of the constraint.
- `values` (required): The values to use for the constraint.
  - For `unique` constraints, this is an array of field names.
  - For `index` constraints, this is an array of field names.

### Example

### Example with Various Types and Defaults

~~~php
<?php

return [
    'up' => [
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
                        'name' => 'identifier',
                        'type' => 'varchar'
                        'size' => 255,
                        'null' => true,
                    ]
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
                    [
                        'type' => 'unique',
                        'name' => 'item_uniq_identifier',
                        'values' => ['identifier'],
                    ],
                    [
                        'type' => 'index',
                        'name' => 'item_idx_identifier',
                        'values' => ['identifier']
                    ],
                ],
            ],
        ],
    ],
    'down' => [
        'actions' => [
            [
                'type' => 'raw_query',
                'query' => 'DROP TABLE IF EXISTS `items`',
                'params' => [],
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
    'up' => [
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
    ],
    'down' => [
        'actions' => [
            [
                'type' => 'raw_query',
                'query' => 'DROP TRIGGER IF EXISTS `update_last_modified`',
                'params' => [],
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
    'up' => [
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
    ],
    'down' => [
        'actions' => [
            [
                'type' => 'raw_query',
                'query' => 'ALTER TABLE `users` DROP COLUMN `last_login`',
                'params' => [],
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
    'up' => [
        'actions' => [
            [
                'type' => 'add_constraint',
                'table_name' => 'users',
                'constraint' => [
                    'type' => 'unique',
                    'name' => 'user_uniq_username_email',
                    'values' => ['username', 'email'],
                ],
            ],
        ],
    ],
    'down' => [
        'actions' => [
            [
                'type' => 'raw_query',
                'query' => 'ALTER TABLE `users` DROP INDEX `user_uniq_username_email`',
                'params' => [],
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
    'up' => [
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
    ],
    'down' => [
        'actions' => [
            [
                'type' => 'raw_query',
                'query' => 'DELETE FROM `users` WHERE `username` = ?',
                'params' => ['admin'],
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
    'up' => [
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
    ],
    'down' => [
        'actions' => [
            [
                'type' => 'modify_column_type',
                'table_name' => 'users',
                'field' => [
                    'name' => 'username',
                    'type' => 'varchar',
                    'size' => 255,
                    'null' => false,
                ],
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
    'up' => [
        'actions' => [
            [
                'type' => 'rename_column',
                'table_name' => 'users',
                'name' => 'email',
                'new_name' => 'user_email',
            ],
        ],
    ],
    'down' => [
        'actions' => [
            [
                'type' => 'rename_column',
                'table_name' => 'users',
                'name' => 'user_email',
                'new_name' => 'email',
            ],
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
    'up' => [
        'actions' => [
            [
                'type' => 'rename_table',
                'table_name' => 'users',
                'new_name' => 'app_users',
            ],
        ],
    ],
    'down' => [
        'actions' => [
            [
                'type' => 'rename_table',
                'table_name' => 'app_users',
                'new_name' => 'users',
            ],
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
    'up' => [
        'actions' => [
            [
                'type' => 'raw_query',
                'query' => 'UPDATE users SET status = ? WHERE last_login < ?',
                'params' => ['inactive', '2023-01-01 00:00:00'],
            ],
        ],
    ],
    'down' => [
        'actions' => [
            [
                'type' => 'raw_query',
                'query' => 'UPDATE users SET status = ? WHERE status = ?',
                'params' => ['active', 'inactive'],
            ],
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
    'up' => [
        'actions' => [
            [
                'type' => 'run_task',
                'task' => 'App\Tasks\CustomMigrationTask',
            ],
        ],
    ],
    'down' => [
        'actions' => [
            [
                'type' => 'run_task',
                'task' => 'App\Tasks\ReverseCustomMigrationTask',
            ],
        ],
    ],
];
~~~

Note: The custom task class must implement the `Task` and be registered in the dependency injection container.
