<?php

return [
    'up' => [
        'actions' => [
            [
                'type' => 'create_table',
                'table_name' => 'verification_codes',
                'fields' => [
                    [
                        'name' => 'guid',
                        'type' => 'varchar',
                        'size' => 40,
                    ],
                    [
                        'name' => 'user_id',
                        'type' => 'foreign_key',
                        'foreign_table' => 'users',
                        'on_delete' => 'cascade',
                        'on_update' => 'cascade',
                        'foreign_field' => 'id',
                    ],
                    [
                        'name' => 'code',
                        'type' => 'varchar',
                        'size' => 50,
                    ],
                    [
                        'name' => 'action',
                        'type' => 'varchar',
                        'size' => 50,
                    ],
                    [
                        'name' => 'attempts',
                        'type' => 'int',
                        'default' => 0,
                    ],
                    [
                        'name' => 'max_attempts',
                        'type' => 'int',
                        'default' => 5,
                    ],
                    [
                        'name' => 'expires_at',
                        'type' => 'int',
                    ],
                    [
                        'name' => 'correct_at',
                        'type' => 'int',
                        'null' => true,
                    ],
                    [
                        'name' => 'invalidated_at',
                        'type' => 'int',
                        'null' => true,
                    ],
                    [
                        'name' => 'processed_at',
                        'type' => 'int',
                        'null' => true,
                    ],
                    [
                        'name' => 'created_at',
                        'type' => 'int',
                        'default' => ['function' => 'UNIX_TIMESTAMP()'],
                    ],
                    [
                        'name' => 'flow_data',
                        'type' => 'json',
                        'null' => true,
                    ],
                ],
                'constraints' => [
                    [
                        'type' => 'unique',
                        'values' => ['guid'],
                    ],
                    [
                        'type' => 'index',
                        'name' => 'idx_verification_codes_guid',
                        'values' => ['guid'],
                    ],
                    [
                        'type' => 'index',
                        'name' => 'idx_verification_codes_expires_at',
                        'values' => ['expires_at'],
                    ],
                ],
            ],
            [
                'type' => 'create_trigger',
                'table_name' => 'verification_codes',
                'trigger' => [
                    'name' => 'verification_code_guid',
                    'time' => 'before',
                    'event' => 'insert',
                    'action' => 'set new.guid = uuid()',
                ],
            ],
        ],
    ],
    'down' => [
        'actions' => [
            [
                'type' => 'raw_query',
                'query' => 'DROP TABLE IF EXISTS `verification_codes`',
                'params' => [],
            ],
        ],
    ],
];
