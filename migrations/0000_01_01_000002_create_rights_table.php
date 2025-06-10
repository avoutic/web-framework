<?php

return [
    'up' => [
        'actions' => [
            [
                'type' => 'create_table',
                'table_name' => 'rights',
                'fields' => [
                    [
                        'name' => 'short_name',
                        'type' => 'varchar',
                        'size' => 255,
                    ],
                    [
                        'name' => 'name',
                        'type' => 'varchar',
                        'size' => 255,
                    ],
                ],
                'constraints' => [
                ],
            ],
            [
                'type' => 'insert_row',
                'table_name' => 'rights',
                'values' => [
                    'short_name' => 'admin',
                    'name' => 'Admin',
                ],
            ],
        ],
    ],
    'down' => [
        'actions' => [
            [
                'type' => 'raw_query',
                'query' => 'DROP TABLE IF EXISTS `rights`',
                'params' => [],
            ],
        ],
    ],
];
