<?php

namespace WebFramework\Core;

class BuildInfoService
{
    public function __construct(
        private RuntimeEnvironment $runtimeEnvironment,
    ) {
    }

    /**
     * Get build info.
     *
     * @return array{commit: null|string, timestamp: string}
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
