<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Entity;

use Carbon\Carbon;

/**
 * Represents a verification code in the system.
 */
class VerificationCode extends EntityCore
{
    protected static string $tableName = 'verification_codes';
    protected static array $baseFields = ['guid', 'user_id', 'code', 'action', 'attempts', 'max_attempts', 'expires_at', 'correct_at', 'invalidated_at', 'processed_at', 'created_at', 'flow_data'];
    protected static array $additionalIdFields = ['guid'];

    private int $id;
    private string $guid;
    private int $userId;
    private string $code = '';
    private string $action = '';
    private int $attempts = 0;
    private int $maxAttempts = 5;
    private int $expiresAt = 0;
    private ?int $correctAt = null;
    private ?int $invalidatedAt = null;
    private ?int $processedAt = null;
    private int $createdAt = 0;
    private ?string $flowData = null;

    /**
     * Get the verification code ID.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the GUID.
     */
    public function getGuid(): string
    {
        return $this->guid;
    }

    /**
     * Set the GUID.
     */
    public function setGuid(string $guid): void
    {
        $this->guid = $guid;
    }

    /**
     * Get the user ID.
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * Set the user ID.
     */
    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * Get the code.
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Set the code.
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * Get the action.
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Set the action.
     */
    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    /**
     * Get the number of attempts.
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }

    /**
     * Set the number of attempts.
     */
    public function setAttempts(int $attempts): void
    {
        $this->attempts = $attempts;
    }

    /**
     * Increment the number of attempts.
     */
    public function incrementAttempts(): void
    {
        $this->attempts++;
    }

    /**
     * Get the maximum number of attempts.
     */
    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * Set the maximum number of attempts.
     */
    public function setMaxAttempts(int $maxAttempts): void
    {
        $this->maxAttempts = $maxAttempts;
    }

    /**
     * Get the expiration timestamp.
     */
    public function getExpiresAt(): int
    {
        return $this->expiresAt;
    }

    /**
     * Set the expiration timestamp.
     */
    public function setExpiresAt(int $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    /**
     * Check if the code is expired.
     */
    public function isExpired(): bool
    {
        if ($this->expiresAt === 0)
        {
            return true;
        }

        return $this->expiresAt < Carbon::now()->getTimestamp();
    }

    /**
     * Get the correct timestamp.
     */
    public function getCorrectAt(): ?int
    {
        return $this->correctAt;
    }

    /**
     * Set the correct timestamp.
     */
    public function setCorrectAt(?int $correctAt): void
    {
        $this->correctAt = $correctAt;
    }

    /**
     * Check if the code has been correct.
     */
    public function isCorrect(): bool
    {
        return $this->correctAt !== null;
    }

    /**
     * Mark the code as correct.
     */
    public function markAsCorrect(): void
    {
        $this->correctAt = Carbon::now()->getTimestamp();
    }

    /**
     * Get the invalidated timestamp.
     */
    public function getInvalidatedAt(): ?int
    {
        return $this->invalidatedAt;
    }

    /**
     * Set the invalidated timestamp.
     */
    public function setInvalidatedAt(?int $invalidatedAt): void
    {
        $this->invalidatedAt = $invalidatedAt;
    }

    /**
     * Check if the code has been invalidated.
     */
    public function isInvalidated(): bool
    {
        return $this->invalidatedAt !== null;
    }

    /**
     * Mark the code as invalidated.
     */
    public function markAsInvalidated(): void
    {
        $this->invalidatedAt = Carbon::now()->getTimestamp();
    }

    /**
     * Get the processed timestamp.
     */
    public function getProcessedAt(): ?int
    {
        return $this->processedAt;
    }

    /**
     * Set the processed timestamp.
     */
    public function setProcessedAt(?int $processedAt): void
    {
        $this->processedAt = $processedAt;
    }

    /**
     * Check if the code has been processed.
     */
    public function isProcessed(): bool
    {
        return $this->processedAt !== null;
    }

    /**
     * Mark the code as processed (prevent replay attacks).
     */
    public function markAsProcessed(): void
    {
        $this->processedAt = Carbon::now()->getTimestamp();
    }

    /**
     * Check if the code has attempts remaining.
     */
    public function hasAttemptsRemaining(): bool
    {
        return $this->attempts < $this->maxAttempts;
    }

    /**
     * Get the created timestamp.
     */
    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    /**
     * Set the created timestamp.
     */
    public function setCreatedAt(int $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Get the flow data.
     *
     * @return array<mixed>
     */
    public function getFlowData(): array
    {
        if ($this->flowData === null)
        {
            return [];
        }

        $decoded = json_decode($this->flowData, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Set the flow data.
     *
     * @param array<mixed> $flowData
     */
    public function setFlowData(array $flowData): void
    {
        $encoded = json_encode($flowData);
        $this->flowData = $encoded === false ? null : $encoded;
    }
}
