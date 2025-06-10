<?php

return [
    'up' => [
        'actions' => [
            [
                'type' => 'create_table',
                'table_name' => 'sessions',
                'fields' => [
                    [
                        'name' => 'user_id',
                        'type' => 'foreign_key',
                        'foreign_table' => 'users',
                        'on_delete' => 'cascade',
                        'on_update' => 'cascade',
                        'foreign_field' => 'id',
                    ],
                    [
                        'name' => 'session_id',
                        'type' => 'varchar',
                        'size' => 255,
                    ],
                    [
                        'name' => 'start',
                        'type' => 'datetime',
                        'default' => ['function' => 'CURRENT_TIMESTAMP'],
                    ],
                    [
                        'name' => 'last_active',
                        'type' => 'datetime',
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
                'query' => 'DROP TABLE IF EXISTS `sessions`',
                'params' => [],
            ],
        ],
    ],
];
