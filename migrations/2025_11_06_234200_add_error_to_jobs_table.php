<?php

return [
    'up' => [
        'actions' => [
            [
                'type' => 'add_column',
                'table_name' => 'jobs',
                'field' => [
                    'name' => 'error',
                    'type' => 'text',
                    'null' => true,
                ],
            ],
            [
                'type' => 'add_column',
                'table_name' => 'jobs',
                'field' => [
                    'name' => 'failed_at',
                    'type' => 'int',
                    'null' => true,
                ],
            ],
        ],
    ],
    'down' => [
        'actions' => [
            [
                'type' => 'raw_query',
                'query' => 'ALTER TABLE `jobs` DROP COLUMN `failed_at`',
                'params' => [],
            ],
            [
                'type' => 'raw_query',
                'query' => 'ALTER TABLE `jobs` DROP COLUMN `error`',
                'params' => [],
            ],
        ],
    ],
];

