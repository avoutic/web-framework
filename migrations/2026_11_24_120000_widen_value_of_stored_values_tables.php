<?php

// A stored value can hold more than a short string, for example the JSON with the
// registration data that is kept until a user is verified. A VARCHAR(45) does not fit that.

return [
    'up' => [
        'actions' => [
            [
                'type' => 'modify_column_type',
                'table_name' => 'stored_user_values',
                'field' => [
                    'name' => 'value',
                    'type' => 'text',
                ],
            ],
            [
                'type' => 'modify_column_type',
                'table_name' => 'stored_values',
                'field' => [
                    'name' => 'value',
                    'type' => 'text',
                ],
            ],
        ],
    ],
    'down' => [
        'actions' => [
            [
                'type' => 'modify_column_type',
                'table_name' => 'stored_user_values',
                'field' => [
                    'name' => 'value',
                    'type' => 'varchar',
                    'size' => 45,
                ],
            ],
            [
                'type' => 'modify_column_type',
                'table_name' => 'stored_values',
                'field' => [
                    'name' => 'value',
                    'type' => 'varchar',
                    'size' => 45,
                ],
            ],
        ],
    ],
];
