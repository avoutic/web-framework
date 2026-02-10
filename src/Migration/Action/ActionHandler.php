<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Migration\Action;

use WebFramework\Migration\MigrationStep;

/**
 * Builds executable steps for a specific migration action type.
 */
interface ActionHandler
{
    /**
     * The action type supported by this handler.
     */
    public function getType(): string;

    /**
     * @param array<string, mixed> $action
     *
     * @return array<MigrationStep>|MigrationStep
     */
    public function buildStep(array $action): array|MigrationStep;
}
