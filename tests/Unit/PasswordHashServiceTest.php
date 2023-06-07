<?php

namespace Tests\Unit;

use WebFramework\Security\PasswordHashService;

/**
 * @internal
 *
 * @coversNothing
 */
final class PasswordHashServiceTest extends \Codeception\Test\Unit
{
    public function testPbkdf2()
    {
        $instance = $this->makeEmptyExcept(
            PasswordHashService::class,
            'pbkdf2',
        );

        verify($instance->pbkdf2('sha256', 'password', 'salt', 1, 20))
            ->equals('120fb6cffcf8b32c43e7225256c4f837a86548c9');

        verify($instance->pbkdf2('sha256', 'password', 'salt', 2, 20))
            ->equals('ae4d0c95af6b46d32d0adff928f06dd02a303f8e');

        verify($instance->pbkdf2('sha256', 'password', 'salt', 4096, 20))
            ->equals('c5e478d59288c841aa530db6845c4c8d962893a0');

        verify($instance->pbkdf2('sha256', 'passwordPASSWORDpassword', 'saltSALTsaltSALTsaltSALTsaltSALTsalt', 4096, 25))
            ->equals('348c89dbcbd32b2f32d814b8116e84cf2b17347ebc1800181c');

        verify($instance->pbkdf2('sha256', "pass\0word", "sa\0lt", 4096, 16))
            ->equals('89b69d0516f829893c696226650a8687');

        verify($instance->pbkdf2('sha256', 'passwd', 'salt', 1, 128))
            ->equals('55ac046e56e3089fec1691c22544b605f94185216dde0465e68b9d57c20dacbc49ca9cccf179b645991664b39d77ef317c71b845b1e30bd509112041d3a19783c294e850150390e1160c34d62e9665d659ae49d314510fc98274cc79681968104b8f89237e69b2d549111868658be62f59bd715cac44a1147ed5317c9bae6b2a');

        verify($instance->pbkdf2('sha256', 'Password', "sa\0lt", 4096, 256))
            ->equals('436c82c6af9010bb0fdb274791934ac7dee21745dd11fb57bb90112ab187c495ad82df776ad7cefb606f34fedca59baa5922a57f3e91bc0e11960da7ec87ed0471b456a0808b60dff757b7d313d4068bf8d337a99caede24f3248f87d1bf16892b70b076a07dd163a8a09db788ae34300ff2f2d0a92c9e678186183622a636f4cbce15680dfea46f6d224e51c299d4946aa2471133a649288eef3e4227b609cf203dba65e9fa69e63d35b6ff435ff51664cbd6773d72ebc341d239f0084b004388d6afa504eee6719a7ae1bb9daf6b7628d851fab335f1d13948e8ee6f7ab033a32df447f8d0950809a70066605d6960847ed436fa52cdfbcf261b44d2a87061');
    }

    public function testGenerateHash()
    {
        $instance = $this->make(
            PasswordHashService::class,
            [
                'getRandomBytes' => '012345678901234567890123',
                'pbkdf2' => 'abcdef',
            ],
        );

        $salt = base64_encode('012345678901234567890123');

        verify($instance->generateHash('password'))
            ->equals("sha256:1000:{$salt}:abcdef");
    }

    public function testGenerateHashTestVector()
    {
        $instance = $this->make(
            PasswordHashService::class,
            [
                'getRandomBytes' => '012345678901234567890123',
            ],
        );

        $salt = base64_encode('012345678901234567890123');

        verify($instance->generateHash('password'))
            ->equals("sha256:1000:{$salt}:38d3b4cf1c237982f9b15d62686604ca150cb1281df0e447");
    }

    public function testCheckPasswordSha256Correct()
    {
        $instance = $this->make(
            PasswordHashService::class,
            [
            ],
        );

        $passwordHash = 'sha256:1000:MDEyMzQ1Njc4OTAxMjM0NTY3ODkwMTIz:38d3b4cf1c237982f9b15d62686604ca150cb1281df0e447';
        $password = 'password';

        verify($instance->checkPassword($passwordHash, $password))
            ->equals(true);
    }

    public function testCheckPasswordSha256Incorrect()
    {
        $instance = $this->make(
            PasswordHashService::class,
            [
            ],
        );

        $passwordHash = 'sha256:1000:MDEyMzQ1Njc4OTAxMjM0NTY3ODkwMTIz:38d3b4cf1c237982f9b15d62686604ca150cb1281df0e447';
        $password = 'Password';

        verify($instance->checkPassword($passwordHash, $password))
            ->equals(false);
    }

    public function testShouldMigrateSha256()
    {
        $instance = $this->makeEmptyExcept(
            PasswordHashService::class,
            'shouldMigrate',
        );

        $passwordHash = 'sha256:1000:MDEyMzQ1Njc4OTAxMjM0NTY3ODkwMTIz:38d3b4cf1c237982f9b15d62686604ca150cb1281df0e447';

        verify($instance->shouldMigrate($passwordHash))
            ->equals(false);
    }

    public function testShouldMigrateUnknown()
    {
        $instance = $this->makeEmptyExcept(
            PasswordHashService::class,
            'shouldMigrate',
        );

        $passwordHash = 'unknown:1000:MDEyMzQ1Njc4OTAxMjM0NTY3ODkwMTIz:38d3b4cf1c237982f9b15d62686604ca150cb1281df0e447';

        verify(function () use ($instance, $passwordHash) {
            $instance->shouldMigrate($passwordHash);
        })
            ->callableThrows(\InvalidArgumentException::class, 'Unknown password hash format');
    }
}
