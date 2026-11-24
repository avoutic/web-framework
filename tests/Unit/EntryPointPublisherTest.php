<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Console\EntryPointPublisher;

/**
 * @internal
 *
 * @covers \WebFramework\Console\EntryPointPublisher
 */
final class EntryPointPublisherTest extends Unit
{
    private string $dir;

    protected function _before(): void
    {
        $this->dir = codecept_output_dir().'publisher_test';
        $this->remove($this->dir);
        mkdir($this->dir.'/package/public', 0o777, true);
        mkdir($this->dir.'/app', 0o777, true);
        file_put_contents($this->dir.'/package/public/index.php', '<?php // new entry point');
    }

    protected function _after(): void
    {
        $this->remove($this->dir);
    }

    private function remove(string $path): void
    {
        if (!file_exists($path))
        {
            return;
        }

        if (is_dir($path))
        {
            foreach (array_diff((array) scandir($path), ['.', '..']) as $entry)
            {
                $this->remove($path.'/'.$entry);
            }

            chmod($path, 0o777);
            rmdir($path);

            return;
        }

        unlink($path);
    }

    public function testPublishesIntoAnExistingDirectory()
    {
        mkdir($this->dir.'/app/public');
        file_put_contents($this->dir.'/app/public/index.php', '<?php // stale entry point');

        $target = (new EntryPointPublisher())->publish($this->dir.'/package/public/index.php', $this->dir.'/app/public/index.php');

        verify($target)->equals($this->dir.'/app/public/index.php');
        verify(file_get_contents($this->dir.'/app/public/index.php'))->equals('<?php // new entry point');
    }

    public function testCreatesTheTargetDirectory()
    {
        (new EntryPointPublisher())->publish($this->dir.'/package/public/index.php', $this->dir.'/app/public/index.php');

        verify(file_get_contents($this->dir.'/app/public/index.php'))->equals('<?php // new entry point');
    }

    public function testMissingSourceFailsAndKeepsTheOldFileReported()
    {
        mkdir($this->dir.'/app/public');
        file_put_contents($this->dir.'/app/public/index.php', '<?php // stale entry point');

        verify(function () {
            (new EntryPointPublisher())->publish($this->dir.'/package/moved/index.php', $this->dir.'/app/public/index.php');
        })->callableThrows(\RuntimeException::class, "Cannot publish: source file '{$this->dir}/package/moved/index.php' does not exist");
    }

    public function testUnwritableTargetFails()
    {
        mkdir($this->dir.'/app/public');
        chmod($this->dir.'/app/public', 0o555);

        verify(function () {
            (new EntryPointPublisher())->publish($this->dir.'/package/public/index.php', $this->dir.'/app/public/index.php');
        })->callableThrows(\RuntimeException::class);
    }
}
