<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Codeception\Test\Unit;
use WebFramework\Core\BuildInfoService;
use WebFramework\Core\RuntimeEnvironment;

/**
 * @internal
 *
 * @covers \WebFramework\Core\BuildInfoService
 */
final class BuildInfoServiceTest extends Unit
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().'/buildinfo_test_'.uniqid();
        mkdir($this->tempDir, 0o777, true);
    }

    protected function tearDown(): void
    {
        $this->cleanupTempDir();
        parent::tearDown();
    }

    private function cleanupTempDir(): void
    {
        if (is_dir($this->tempDir))
        {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->tempDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $file)
            {
                if ($file->isDir())
                {
                    rmdir($file->getRealPath());
                }
                else
                {
                    unlink($file->getRealPath());
                }
            }
            rmdir($this->tempDir);
        }
    }

    public function testGetInfoWhenFilesDoNotExist()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $runtimeEnvironment = $this->make(RuntimeEnvironment::class, [
            'getAppDir' => $this->tempDir,
        ]);

        $service = new BuildInfoService($runtimeEnvironment);
        $info = $service->getInfo();

        verify($info['commit'])->equals(null);
        verify($info['timestamp'])->equals('2025-01-01 12:00');
    }

    public function testGetInfoWhenFilesExist()
    {
        $commitHash = 'a1b2c3d4e5f6g7h8i9j0';
        $timestamp = '2024-01-15 14:30:00';

        file_put_contents($this->tempDir.'/build_commit', $commitHash);
        file_put_contents($this->tempDir.'/build_timestamp', $timestamp);

        $runtimeEnvironment = $this->make(RuntimeEnvironment::class, [
            'getAppDir' => $this->tempDir,
        ]);

        $service = new BuildInfoService($runtimeEnvironment);
        $info = $service->getInfo();

        verify($info['commit'])->equals('a1b2c3d4');
        verify($info['timestamp'])->equals($timestamp);
    }

    public function testGetInfoCommitTruncatedToEightCharacters()
    {
        $commitHash = 'verylongcommithash1234567890';
        $timestamp = '2024-01-15 14:30:00';

        file_put_contents($this->tempDir.'/build_commit', $commitHash);
        file_put_contents($this->tempDir.'/build_timestamp', $timestamp);

        $runtimeEnvironment = $this->make(RuntimeEnvironment::class, [
            'getAppDir' => $this->tempDir,
        ]);

        $service = new BuildInfoService($runtimeEnvironment);
        $info = $service->getInfo();

        verify($info['commit'])->equals('verylong');
    }

    public function testGetInfoCommitShorterThanEightCharacters()
    {
        $commitHash = 'abc123';
        $timestamp = '2024-01-15 14:30:00';

        file_put_contents($this->tempDir.'/build_commit', $commitHash);
        file_put_contents($this->tempDir.'/build_timestamp', $timestamp);

        $runtimeEnvironment = $this->make(RuntimeEnvironment::class, [
            'getAppDir' => $this->tempDir,
        ]);

        $service = new BuildInfoService($runtimeEnvironment);
        $info = $service->getInfo();

        verify($info['commit'])->equals('abc123');
    }

    public function testGetInfoWhenBuildCommitFileMissing()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $timestamp = '2024-01-15 14:30:00';
        file_put_contents($this->tempDir.'/build_timestamp', $timestamp);

        $runtimeEnvironment = $this->make(RuntimeEnvironment::class, [
            'getAppDir' => $this->tempDir,
        ]);

        $service = new BuildInfoService($runtimeEnvironment);
        $info = $service->getInfo();

        verify($info['commit'])->equals(null);
        verify($info['timestamp'])->equals('2025-01-01 12:00');
    }

    public function testGetInfoWhenBuildTimestampFileMissing()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $commitHash = 'a1b2c3d4e5f6';
        file_put_contents($this->tempDir.'/build_commit', $commitHash);

        $runtimeEnvironment = $this->make(RuntimeEnvironment::class, [
            'getAppDir' => $this->tempDir,
        ]);

        $service = new BuildInfoService($runtimeEnvironment);
        $info = $service->getInfo();

        verify($info['commit'])->equals(null);
        verify($info['timestamp'])->equals('2025-01-01 12:00');
    }

    public function testGetInfoWithEmptyCommitFile()
    {
        $timestamp = '2024-01-15 14:30:00';
        file_put_contents($this->tempDir.'/build_commit', '');
        file_put_contents($this->tempDir.'/build_timestamp', $timestamp);

        $runtimeEnvironment = $this->make(RuntimeEnvironment::class, [
            'getAppDir' => $this->tempDir,
        ]);

        $service = new BuildInfoService($runtimeEnvironment);
        $info = $service->getInfo();

        verify($info['commit'])->equals('');
        verify($info['timestamp'])->equals($timestamp);
    }
}
