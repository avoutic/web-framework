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
