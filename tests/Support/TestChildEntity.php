<?php

namespace Tests\Support;

/**
 * Entity that extends another entity and only declares its own fields.
 */
class TestChildEntity extends TestEntity
{
    protected static array $baseFields = ['nickname', 'api_token'];
    protected static array $privateFields = ['api_token'];
    protected static array $fillableFields = ['nickname'];

    protected string $nickname = '';
    protected string $apiToken = '';

    public function setNickname(string $nickname): void
    {
        $this->nickname = $nickname;
    }

    public function getNickname(): string
    {
        return $this->nickname;
    }

    public function setApiToken(string $apiToken): void
    {
        $this->apiToken = $apiToken;
    }
}
