<?php

return [
    'up' => [
        'actions' => [
            [
                'type' => 'add_column',
                'table_name' => 'jobs',
                'field' => [
                    'name' => 'max_attempts',
                    'type' => 'int',
                    'default' => 3,
                ],
            ],
        ],
    ],
    'down' => [
        'actions' => [
            [
                'type' => 'raw_query',
                'query' => 'ALTER TABLE `jobs` DROP COLUMN `max_attempts`',
                'params' => [],
            ],
        ],
    ],
];

