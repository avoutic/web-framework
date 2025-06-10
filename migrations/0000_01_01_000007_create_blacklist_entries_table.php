<?php

return [
    'up' => [
        'actions' => [
            [
                'type' => 'create_table',
                'table_name' => 'blacklist_entries',
                'fields' => [
                    [
                        'name' => 'ip',
                        'type' => 'varchar',
                        'size' => 40,
                    ],
                    [
                        'name' => 'user_id',
                        'type' => 'int',
                        'null' => true,
                    ],
                    [
                        'name' => 'severity',
                        'type' => 'int',
                    ],
                    [
                        'name' => 'reason',
                        'type' => 'varchar',
                        'size' => 100,
                    ],
                    [
                        'name' => 'timestamp',
                        'type' => 'int',
                    ],
                ],
                'constraints' => [
                ],
            ],
        ],
    ],
    'down' => [
        'actions' => [
            [
                'type' => 'raw_query',
                'query' => 'DROP TABLE IF EXISTS `blacklist_entries`',
                'params' => [],
            ],
        ],
    ],
];
