<?php

namespace WebFramework\Core;

interface Database
{
    /**
     * @param array<null|bool|float|int|string> $valueArray
     */
    public function query(string $queryStr, array $valueArray): DatabaseResultWrapper|false;

    /**
     * @param array<null|bool|float|int|string> $params
     */
    public function insertQuery(string $query, array $params): int|false;

    public function getLastError(): string;

    public function tableExists(string $tableName): bool;

    public function startTransaction(): void;

    public function commitTransaction(): void;

    public function getTransactionDepth(): int;
}
