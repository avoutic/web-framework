<?php

namespace WebFramework\Core;

interface Database
{
    /**
     * @param array<null|bool|float|int|string> $valueArray
     */
    public function query(string $queryStr, array $valueArray, string $exceptionMessage = ''): DatabaseResultWrapper;

    /**
     * @param array<null|bool|float|int|string> $params
     */
    public function insertQuery(string $query, array $params, string $exceptionMessage = ''): int;

    public function getLastError(): string;

    public function tableExists(string $tableName): bool;

    public function startTransaction(): void;

    public function commitTransaction(): void;

    public function getTransactionDepth(): int;
}
