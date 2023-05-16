<?php

namespace WebFramework\Core;

interface Database
{
    /**
     * @param array<null|bool|float|int|string> $value_array
     */
    public function query(string $query_str, array $value_array): DatabaseResultWrapper|false;

    /**
     * @param array<null|bool|float|int|string> $params
     */
    public function insert_query(string $query, array $params): int|false;

    public function get_last_error(): string;

    public function table_exists(string $table_name): bool;

    public function start_transaction(): void;

    public function commit_transaction(): void;

    public function get_transaction_depth(): int;
}
