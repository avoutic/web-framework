<?php

return [
    'up' => [
        'actions' => [
            [
                'type' => 'add_column',
                'table_name' => 'jobs',
                'field' => [
                    'name' => 'completed_at',
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
                'query' => 'ALTER TABLE `jobs` DROP COLUMN `completed_at`',
                'params' => [],
            ],
        ],
    ],
];

