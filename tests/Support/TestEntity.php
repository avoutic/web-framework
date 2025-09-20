<?php

namespace Tests\Support;

use WebFramework\Entity\EntityCore;

class TestEntity extends EntityCore
{
    protected static string $tableName = 'test_entities';
    protected static array $baseFields = ['name', 'email', 'age', 'active', 'secret_field', 'created_at'];
    protected static array $privateFields = ['secret_field'];
    protected static array $fillableFields = ['name', 'email', 'age', 'active'];

    protected int $id;
    protected string $name = '';
    protected string $email = '';
    protected int $age = 0;
    protected bool $active = false;
    protected string $secretField = '';
    protected int $createdAt = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function setAge(int $age): void
    {
        $this->age = $age;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getSecretField(): string
    {
        return $this->secretField;
    }

    public function setSecretField(string $secretField): void
    {
        $this->secretField = $secretField;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function setCreatedAt(int $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
