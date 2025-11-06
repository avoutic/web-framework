<?php

return [
    'up' => [
        'actions' => [
            [
                'type' => 'raw_query',
                'query' => 'CREATE INDEX `idx_jobs_queue_available_attempts_reserved` ON `jobs` (`queue_name`, `available_at`, `attempts`, `reserved_at`)',
                'params' => [],
            ],
        ],
    ],
    'down' => [
        'actions' => [
            [
                'type' => 'raw_query',
                'query' => 'DROP INDEX `idx_jobs_queue_available_attempts_reserved` ON `jobs`',
                'params' => [],
            ],
        ],
    ],
];

