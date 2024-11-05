<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Core;

use Sentry\ClientInterface;
use Sentry\SentrySdk;
use Sentry\Tracing\SpanContext;
use Sentry\Tracing\Transaction;
use Sentry\Tracing\TransactionContext;

/**
 * Class SentryInstrumentation.
 *
 * Implements the Instrumentation interface using Sentry for performance monitoring and tracing.
 */
class SentryInstrumentation implements Instrumentation
{
    /** @var null|Transaction The current transaction */
    private ?Transaction $currentTransaction = null;

    /**
     * SentryInstrumentation constructor.
     *
     * @param ClientInterface $client The Sentry client
     */
    public function __construct(
        ClientInterface $client,
    ) {
        SentrySdk::getCurrentHub()->bindClient($client);
    }

    public function getCurrentTransaction(): mixed
    {
        return $this->currentTransaction;
    }

    public function startTransaction(string $op, string $name): mixed
    {
        // Setup context for the full transaction
        $transactionContext = new TransactionContext();
        $transactionContext->setOp($op);
        $transactionContext->setName($name);

        // Start the transaction
        $this->currentTransaction = \Sentry\startTransaction($transactionContext);

        // Set the current transaction as the current span so we can retrieve it later
        SentrySdk::getCurrentHub()->setSpan($this->currentTransaction);

        return $this->currentTransaction;
    }

    public function finishTransaction(mixed $transaction): void
    {
        $transaction->finish();
    }

    public function setTransactionName(mixed $transaction, string $name): void
    {
        $transaction->setName($name);
    }

    public function startSpan(string $op, string $description = ''): mixed
    {
        $parent = null;
        $span = null;

        $parent = SentrySdk::getCurrentHub()->getSpan();
        $span = null;

        // Check if we have a parent span (this is the case if we started a transaction)
        if ($parent !== null)
        {
            $context = new SpanContext();
            $context->setOp($op);
            $context->setDescription($description);
            $span = $parent->startChild($context);

            // Set the current span to the span we just started
            SentrySdk::getCurrentHub()->setSpan($span);
        }

        return [
            'parent' => $parent,
            'span' => $span,
        ];
    }

    public function finishSpan(mixed $span): void
    {
        if ($span['span'] === null)
        {
            return;
        }

        // We only have a span if we started a span earlier
        $span['span']->finish();

        // Restore the current span back to the parent span
        SentrySdk::getCurrentHub()->setSpan($span['parent']);
    }
}
