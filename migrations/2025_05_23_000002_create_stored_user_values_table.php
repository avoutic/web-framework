<?php

return [
    'up' => [
        'actions' => [
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
                'query' => 'INSERT INTO stored_user_values (user_id, module, name, value) SELECT user_id, module, name, value FROM user_config_values',
                'params' => [],
            ],
        ],
    ],
    'down' => [
        'actions' => [
            [
                'type' => 'raw_query',
                'query' => 'DROP TABLE IF EXISTS `stored_user_values`',
                'params' => [],
            ],
        ],
    ],
];
