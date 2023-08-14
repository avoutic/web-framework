<?php

namespace WebFramework\Core;

interface Instrumentation
{
    public function startTransaction(string $op, string $name): mixed;

    public function finishTransaction(mixed $transaction): void;

    public function startSpan(string $op, string $description = ''): mixed;

    public function finishSpan(mixed $span): void;
}
