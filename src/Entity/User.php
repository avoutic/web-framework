<?php

namespace WebFramework\Entity;

use WebFramework\Core\EntityCore;

/**
 * Represents a user in the system.
 */
class User extends EntityCore
{
    protected static string $tableName = 'users';
    protected static array $baseFields = ['username', 'email', 'solid_password', 'terms_accepted', 'registered', 'verified', 'last_login', 'failed_login'];
    protected static array $privateFields = ['solid_password'];

    // Protected because User is often extended with project specific fields
    protected int $id;
    protected string $username = '';
    protected string $email = '';
    protected string $solidPassword = '';
    protected int $termsAccepted = 0;
    protected bool $verified = false;
    protected int $registered = 0;
    protected int $lastLogin = 0;
    protected int $failedLogin = 0;

    /**
     * Get the user ID.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the username.
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Set the username.
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    /**
     * Get the user's email address.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Set the user's email address.
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * Get the registration timestamp.
     */
    public function getRegistered(): int
    {
        return $this->registered;
    }

    /**
     * Get the timestamp when terms were accepted.
     */
    public function getTermsAccepted(): int
    {
        return $this->termsAccepted;
    }

    /**
     * Set the timestamp when terms were accepted.
     */
    public function setTermsAccepted(int $time): void
    {
        $this->termsAccepted = $time;
    }

    /**
     * Check if the user is verified.
     */
    public function isVerified(): bool
    {
        return $this->verified == 1;
    }

    /**
     * Mark the user as verified.
     */
    public function setVerified(): void
    {
        $this->verified = true;
    }

    /**
     * Get the hashed password.
     */
    public function getSolidPassword(): string
    {
        return $this->solidPassword;
    }

    /**
     * Set the hashed password.
     *
     * @throws \InvalidArgumentException If an empty hash is provided
     */
    public function setSolidPassword(string $passwordHash): void
    {
        if (!strlen($passwordHash))
        {
            throw new \InvalidArgumentException('No hash provided');
        }

        $this->solidPassword = $passwordHash;
    }

    /**
     * Get the number of failed login attempts.
     */
    public function getFailedLogin(): int
    {
        return $this->failedLogin;
    }

    /**
     * Increment the number of failed login attempts.
     */
    public function incrementFailedLogin(): void
    {
        $this->failedLogin++;
    }

    /**
     * Get the timestamp of the last successful login.
     */
    public function getLastLogin(): int
    {
        return $this->lastLogin;
    }

    /**
     * Set the timestamp of the last successful login and reset failed login attempts.
     */
    public function setLastLogin(int $timestamp): void
    {
        $this->lastLogin = $timestamp;
        $this->failedLogin = 0;
    }
}
