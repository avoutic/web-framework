<?php

return [
    'up' => [
        'actions' => [
            [
                'type' => 'create_table',
                'table_name' => 'users',
                'fields' => [
                    [
                        'name' => 'username',
                        'type' => 'varchar',
                        'size' => 255,
                    ],
                    [
                        'name' => 'solid_password',
                        'type' => 'varchar',
                        'size' => 255,
                    ],
                    [
                        'name' => 'name',
                        'type' => 'varchar',
                        'size' => 255,
                        'null' => true,
                        'default' => '',
                    ],
                    [
                        'name' => 'email',
                        'type' => 'varchar',
                        'size' => 255,
                    ],
                    [
                        'name' => 'registered',
                        'type' => 'int',
                    ],
                    [
                        'name' => 'verified',
                        'type' => 'boolean',
                        'default' => '0',
                    ],
                    [
                        'name' => 'failed_login',
                        'type' => 'int',
                        'default' => '0',
                    ],
                    [
                        'name' => 'terms_accepted',
                        'type' => 'int',
                        'default' => '0',
                    ],
                    [
                        'name' => 'last_login',
                        'type' => 'int',
                        'default' => '0',
                    ],
                ],
                'constraints' => [
                    [
                        'type' => 'unique',
                        'values' => ['username'],
                    ],
                ],
            ],
        ],
    ],
    'down' => [
        'actions' => [
            [
                'type' => 'raw_query',
                'query' => 'DROP TABLE IF EXISTS `users`',
                'params' => [],
            ],
        ],
    ],
];
