<?php

return [
    'actions' => [
        [
            'type' => 'create_table',
            'table_name' => 'stored_values',
            'fields' => [
                [
                    'name' => 'module',
                    'type' => 'varchar',
                    'size' => 45,
                ],
                [
                    'name' => 'name',
                    'type' => 'varchar',
                    'size' => 45,
                ],
                [
                    'name' => 'value',
                    'type' => 'varchar',
                    'size' => 45,
                ],
            ],
            'constraints' => [
                [
                    'type' => 'unique',
                    'values' => ['module', 'name'],
                ],
            ],
        ],
        [
            'type' => 'create_table',
            'table_name' => 'stored_user_values',
            'fields' => [
                [
                    'name' => 'user_id',
                    'type' => 'foreign_key',
                    'foreign_table' => 'users',
                    'on_delete' => 'cascade',
                    'on_update' => 'cascade',
                    'foreign_field' => 'id',
                ],
                [
                    'name' => 'module',
                    'type' => 'varchar',
                    'size' => 45,
                ],
                [
                    'name' => 'name',
                    'type' => 'varchar',
                    'size' => 45,
                ],
                [
                    'name' => 'value',
                    'type' => 'varchar',
                    'size' => 45,
                ],
            ],
            'constraints' => [
                [
                    'type' => 'unique',
                    'values' => ['user_id', 'module', 'name'],
                ],
            ],
        ],
        [
            'type' => 'raw_query',
            'query' => 'INSERT INTO stored_values (module, name, value) FROM SELECT module, name, value FROM config_values',
            'params' => [],
        ],
        [
            'type' => 'raw_query',
            'query' => 'INSERT INTO stored_user_values (user_id, module, name, value) FROM SELECT user_id, module, name, value FROM user_config_values',
            'params' => [],
        ],
        [
            'type' => 'raw_query',
            'query' => 'UPDATE stored_values SET "value" = "3" WHERE "module" = "db" AND "name" = "wf_db_version"',
            'params' => [],
        ],
    ],
];
