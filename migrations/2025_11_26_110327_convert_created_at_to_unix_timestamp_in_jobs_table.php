<?php

return [
    'up' => [
        'actions' => [
            [
                'type' => 'raw_query',
                'query' => <<<'SQL'
ALTER TABLE `jobs`
    ADD COLUMN `created_at_temp` INT NOT NULL DEFAULT 0 AFTER `available_at`
SQL,
                'params' => [],
            ],
            [
                'type' => 'raw_query',
                'query' => <<<'SQL'
UPDATE `jobs`
SET `created_at_temp` = UNIX_TIMESTAMP(`created_at`)
WHERE `created_at` IS NOT NULL
SQL,
                'params' => [],
            ],
            [
                'type' => 'raw_query',
                'query' => <<<'SQL'
ALTER TABLE `jobs`
    DROP COLUMN `created_at`
SQL,
                'params' => [],
            ],
            [
                'type' => 'raw_query',
                'query' => <<<'SQL'
ALTER TABLE `jobs`
    CHANGE COLUMN `created_at_temp` `created_at` INT NOT NULL DEFAULT 0
SQL,
                'params' => [],
            ],
        ],
    ],
    'down' => [
        'actions' => [
            [
                'type' => 'raw_query',
                'query' => <<<'SQL'
ALTER TABLE `jobs`
    ADD COLUMN `created_at_temp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `available_at`
SQL,
                'params' => [],
            ],
            [
                'type' => 'raw_query',
                'query' => <<<'SQL'
UPDATE `jobs`
SET `created_at_temp` = FROM_UNIXTIME(`created_at`)
WHERE `created_at` IS NOT NULL
SQL,
                'params' => [],
            ],
            [
                'type' => 'raw_query',
                'query' => <<<'SQL'
ALTER TABLE `jobs`
    DROP COLUMN `created_at`
SQL,
                'params' => [],
            ],
            [
                'type' => 'raw_query',
                'query' => <<<'SQL'
ALTER TABLE `jobs`
    CHANGE COLUMN `created_at_temp` `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
SQL,
                'params' => [],
            ],
        ],
    ],
];

