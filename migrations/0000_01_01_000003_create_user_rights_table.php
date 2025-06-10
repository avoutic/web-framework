<?php

return [
    'up' => [
        'actions' => [
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
        ],
    ],
    'down' => [
        'actions' => [
            [
                'type' => 'raw_query',
                'query' => 'DROP TABLE IF EXISTS `user_rights`',
                'params' => [],
            ],
        ],
    ],
];
