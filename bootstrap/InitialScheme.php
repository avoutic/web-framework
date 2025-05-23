<?php

return [
    'target_version' => 1,
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
        [
            'type' => 'create_table',
            'table_name' => 'rights',
            'fields' => [
                [
                    'name' => 'short_name',
                    'type' => 'varchar',
                    'size' => 255,
                ],
                [
                    'name' => 'name',
                    'type' => 'varchar',
                    'size' => 255,
                ],
            ],
            'constraints' => [
            ],
        ],
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
        [
            'type' => 'create_table',
            'table_name' => 'user_rights',
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
                    'name' => 'right_id',
                    'type' => 'foreign_key',
                    'foreign_table' => 'rights',
                    'foreign_field' => 'id',
                    'on_delete' => 'cascade',
                    'on_update' => 'cascade',
                ],
            ],
            'constraints' => [
                [
                    'type' => 'unique',
                    'values' => ['user_id', 'right_id'],
                ],
            ],
        ],
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
        [
            'type' => 'create_table',
            'table_name' => 'stored_values',
            'fields' => [
                [
                    'name' => 'module',
                    'type' => 'varchar',
                    'size' => 45,
                ],
                [
                    'name' => 'name',
                    'type' => 'varchar',
                    'size' => 45,
                ],
                [
                    'name' => 'value',
                    'type' => 'varchar',
                    'size' => 45,
                ],
            ],
            'constraints' => [
                [
                    'type' => 'unique',
                    'values' => ['module', 'name'],
                ],
            ],
        ],
        [
            'type' => 'create_table',
            'table_name' => 'stored_user_values',
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
                    'name' => 'module',
                    'type' => 'varchar',
                    'size' => 45,
                ],
                [
                    'name' => 'name',
                    'type' => 'varchar',
                    'size' => 45,
                ],
                [
                    'name' => 'value',
                    'type' => 'varchar',
                    'size' => 45,
                ],
            ],
            'constraints' => [
                [
                    'type' => 'unique',
                    'values' => ['user_id', 'module', 'name'],
                ],
            ],
        ],
        [
            'type' => 'insert_row',
            'table_name' => 'stored_values',
            'values' => [
                'module' => 'db',
                'name' => 'wf_db_version',
                'value' => '3',
            ],
        ],
        [
            'type' => 'insert_row',
            'table_name' => 'stored_values',
            'values' => [
                'module' => 'db',
                'name' => 'app_db_version',
                'value' => '0',
            ],
        ],
        [
            'type' => 'insert_row',
            'table_name' => 'rights',
            'values' => [
                'short_name' => 'admin',
                'name' => 'Admin',
            ],
        ],
    ],
];
