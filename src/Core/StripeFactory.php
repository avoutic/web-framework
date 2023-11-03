<?php

namespace WebFramework\Core;

use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;

class StripeFactory
{
    public function __construct(
        private StripeClient $stripe,
        private string $endpointSecret,
        private bool $production,
    ) {
    }

    public function verifyRequest(string $payload, string $sigHeader): bool
    {
        try
        {
            if ($this->production)
            {
                $event = \Stripe\Webhook::constructEvent(
                    $payload,
                    $sigHeader,
                    $this->endpointSecret,
                );
            }
            else
            {
                // 10 year tolerance for testing
                //
                $event = \Stripe\Webhook::constructEvent(
                    $payload,
                    $sigHeader,
                    $this->endpointSecret,
                    10 * 365 * 24 * 60 * 60,
                );
            }
        }
        catch (\UnexpectedValueException $e)
        {
            return false;
        }
        catch (SignatureVerificationException $e)
        {
            return false;
        }

        return true;
    }

    // Customer object
    //
    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    public function createCustomer(array $data): array
    {
        $customer = \Stripe\Customer::create($data);

        return $customer->toArray();
    }

    /**
     * @return array<mixed>
     */
    public function getCustomerData(string $customerId): array
    {
        $customer = \Stripe\Customer::retrieve(
            [
                'id' => $customerId,
            ]
        );

        return $customer->toArray();
    }

    // Invoice object
    //
    /**
     * @return array<mixed>
     */
    public function getInvoiceData(string $invoiceId): array
    {
        $invoice = \Stripe\Invoice::retrieve(
            [
                'id' => $invoiceId,
            ]
        );

        return $invoice->toArray();
    }

    // Price object
    //
    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    public function createPrice(array $data): array
    {
        $price = $this->stripe->prices->create($data);

        return $price->toArray();
    }

    // Product object
    //
    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    public function createProduct(array $data): array
    {
        $product = $this->stripe->products->create($data);

        return $product->toArray();
    }

    /**
     * @param array<mixed> $filter
     *
     * @return array<mixed>
     */
    public function getProductsData(array $filter = []): array
    {
        $products = \Stripe\Product::all();

        $data = $products->toArray();

        return $data['data'];
    }

    // Subscription object
    //
    public function cancelSubscription(string $subscriptionId): bool
    {
        $subscription = \Stripe\Subscription::retrieve(
            [
                'id' => $subscriptionId,
            ]
        );

        $subscription->cancel();

        return true;
    }

    /**
     * @param array<string> $expand
     *
     * @return array<mixed>
     */
    public function getSubscriptionData(string $subscriptionId, array $expand = []): array
    {
        $subscription = \Stripe\Subscription::retrieve(
            [
                'id' => $subscriptionId,
                'expand' => $expand,
            ]
        );

        return $subscription->toArray();
    }

    // Webhook related
    //
    /**
     * @return array<string>
     */
    public function getWebhookEvents(): array
    {
        $webhooks = $this->stripe->webhookEndpoints->all();
        if (count($webhooks) !== 1)
        {
            throw new \RuntimeException('Not exactly 1 webhook in place');
        }

        return $webhooks->data[0]->enabled_events;
    }
}
