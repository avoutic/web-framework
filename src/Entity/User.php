<?php

namespace WebFramework\Entity;

use WebFramework\Core\EntityCore;

class User extends EntityCore
{
    public static string $tableName = 'users';
    public static array $baseFields = ['username', 'email', 'solid_password', 'terms_accepted', 'registered', 'verified', 'last_login', 'failed_login'];
    public static array $privateFields = ['solid_password'];

    protected int $id;
    protected string $username = '';
    protected string $email = '';
    protected string $solidPassword = '';
    protected int $termsAccepted = 0;
    protected bool $verified = false;
    protected int $registered = 0;
    protected int $lastLogin = 0;
    protected int $failedLogin = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getRegistered(): int
    {
        return $this->registered;
    }

    public function getTermsAccepted(): int
    {
        return $this->termsAccepted;
    }

    public function setTermsAccepted(int $time): void
    {
        $this->termsAccepted = $time;
    }

    public function isVerified(): bool
    {
        return $this->verified == 1;
    }

    public function setVerified(): void
    {
        $this->verified = true;
    }

    public function getSolidPassword(): string
    {
        return $this->solidPassword;
    }

    public function setSolidPassword(string $passwordHash): void
    {
        if (!strlen($passwordHash))
        {
            throw new \InvalidArgumentException('No hash provided');
        }

        $this->solidPassword = $passwordHash;
    }

    public function getFailedLogin(): int
    {
        return $this->failedLogin;
    }

    public function incrementFailedLogin(): void
    {
        $this->failedLogin++;
    }

    public function setLastLogin(int $timestamp): void
    {
        $this->lastLogin = $timestamp;
        $this->failedLogin = 0;
    }
}
