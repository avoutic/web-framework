<?php

namespace WebFramework\Core;

use Sentry\ClientInterface;
use Sentry\Tracing\Transaction;

class SentryInstrumentation implements Instrumentation
{
    private ?Transaction $currentTransaction = null;

    public function __construct(
        ClientInterface $client,
    ) {
        \Sentry\SentrySdk::getCurrentHub()->bindClient($client);
    }

    public function getCurrentTransaction(): mixed
    {
        return $this->currentTransaction;
    }

    public function startTransaction(string $op, string $name): mixed
    {
        // Setup context for the full transaction
        //
        $transactionContext = new \Sentry\Tracing\TransactionContext();
        $transactionContext->setOp($op);
        $transactionContext->setName($name);

        // Start the transaction
        //
        $this->currentTransaction = \Sentry\startTransaction($transactionContext);

        // Set the current transaction as the current span so we can retrieve >
        //
        \Sentry\SentrySdk::getCurrentHub()->setSpan($this->currentTransaction);

        return $this->currentTransaction;
    }

    public function finishTransaction(mixed $transaction): void
    {
        $transaction->finish();
    }

    public function startSpan(string $op, string $description = ''): mixed
    {
        $parent = null;
        $span = null;

        $parent = \Sentry\SentrySdk::getCurrentHub()->getSpan();
        $span = null;

        // Check if we have a parent span (this is the case if we started a tr>
        //
        if ($parent !== null)
        {
            $context = new \Sentry\Tracing\SpanContext();
            $context->setOp($op);
            $context->setDescription($description);
            $span = $parent->startChild($context);

            // Set the current span to the span we just started
            //
            \Sentry\SentrySdk::getCurrentHub()->setSpan($span);
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
        //
        $span['span']->finish();

        // Restore the current span back to the parent span
        //
        \Sentry\SentrySdk::getCurrentHub()->setSpan($span['parent']);
    }
}
