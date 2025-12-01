<?php

return [
    'up' => [
        'actions' => [
            [
                'type' => 'add_constraint',
                'table_name' => 'jobs',
                'constraint' => [
                    'type' => 'index',
                    'name' => 'idx_jobs_queue_available_reserved_completed',
                    'values' => ['queue_name', 'available_at', 'reserved_at', 'completed_at'],
                ],
            ],
        ],
    ],
    'down' => [
        'actions' => [
            [
                'type' => 'raw_query',
                'query' => 'DROP INDEX `idx_jobs_queue_available_reserved_completed` ON `jobs`',
                'params' => [],
            ],
        ],
    ],
];

