<?php

return [
    'up' => [
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
                'type' => 'raw_query',
                'query' => 'INSERT INTO stored_values (module, name, value) SELECT module, name, value FROM config_values',
                'params' => [],
            ],
        ],
    ],
    'down' => [
        'actions' => [
            [
                'type' => 'raw_query',
                'query' => 'DROP TABLE IF EXISTS `stored_values`',
                'params' => [],
            ],
        ],
    ],
];
