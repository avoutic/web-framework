<?php

return [
    'up' => [
        'actions' => [
            [
                'type' => 'add_column',
                'table_name' => 'users',
                'field' => [
                    'name' => 'verified_at',
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
                'query' => 'ALTER TABLE `users` DROP COLUMN `verified_at`',
                'params' => [],
            ],
        ],
    ],
];

