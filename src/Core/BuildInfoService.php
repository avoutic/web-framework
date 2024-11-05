<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Core;

/**
 * Class BuildInfoService.
 *
 * Provides information about the current build of the application.
 */
class BuildInfoService
{
    /**
     * BuildInfoService constructor.
     *
     * @param RuntimeEnvironment $runtimeEnvironment The runtime environment service
     */
    public function __construct(
        private RuntimeEnvironment $runtimeEnvironment,
    ) {}

    /**
     * Get build information.
     *
     * @return array{commit: null|string, timestamp: string} An array containing the commit hash and build timestamp
     *
     * @throws \RuntimeException If unable to read build information files
     */
    public function getInfo(): array
    {
        if (!file_exists($this->runtimeEnvironment->getAppDir().'/build_commit') || !file_exists($this->runtimeEnvironment->getAppDir().'/build_timestamp'))
        {
            return [
                'commit' => null,
                'timestamp' => date('Y-m-d H:i'),
            ];
        }

        $commit = file_get_contents($this->runtimeEnvironment->getAppDir().'/build_commit');
        if ($commit === false)
        {
            throw new \RuntimeException('Failed to retrieve build_commit');
        }

        $commit = substr($commit, 0, 8);

        $buildTime = file_get_contents($this->runtimeEnvironment->getAppDir().'/build_timestamp');
        if ($buildTime === false)
        {
            throw new \RuntimeException('Failed to retrieve build_timestamp');
        }

        return [
            'commit' => $commit,
            'timestamp' => $buildTime,
        ];
    }
}
