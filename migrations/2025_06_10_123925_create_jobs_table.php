<?php

return [
    'up' => [
        'actions' => [
            [
                'type' => 'create_table',
                'table_name' => 'jobs',
                'fields' => [
                    [
                        'name' => 'queue_name',
                        'type' => 'varchar',
                        'size' => 255,
                    ],
                    [
                        'name' => 'job_data',
                        'type' => 'longtext',
                    ],
                    [
                        'name' => 'available_at',
                        'type' => 'int',
                    ],
                    [
                        'name' => 'created_at',
                        'type' => 'datetime',
                        'default' => ['function' => 'CURRENT_TIMESTAMP'],
                    ],
                    [
                        'name' => 'attempts',
                        'type' => 'int',
                        'default' => 0,
                    ],
                ],
                'constraints' => [],
            ],
        ],
    ],
    'down' => [
        'actions' => [
            [
                'type' => 'raw_query',
                'query' => 'DROP TABLE IF EXISTS `jobs`',
                'params' => [],
            ],
        ],
    ],
];
