<?php

// The contents of user_config_values were copied to stored_user_values, and the framework
// no longer uses the table. It is renamed to make that clear. It is not dropped: removing
// data is up to the operator of the application.

return [
    'up' => [
        'actions' => [
            [
                'type' => 'rename_table',
                'table_name' => 'user_config_values',
                'new_name' => 'user_config_values_deprecated',
            ],
        ],
    ],
    'down' => [
        'actions' => [
            [
                'type' => 'rename_table',
                'table_name' => 'user_config_values_deprecated',
                'new_name' => 'user_config_values',
            ],
        ],
    ],
];
