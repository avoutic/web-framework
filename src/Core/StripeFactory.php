<?php

namespace WebFramework\Core;

use Stripe\Customer;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Invoice;
use Stripe\Product;
use Stripe\StripeClient;
use Stripe\Subscription;
use Stripe\Webhook;

/**
 * Class StripeFactory.
 *
 * Provides methods for interacting with the Stripe API.
 */
class StripeFactory
{
    /**
     * StripeFactory constructor.
     *
     * @param RuntimeEnvironment $runtimeEnvironment The runtime environment service
     * @param StripeClient       $stripe             The Stripe client
     * @param string             $endpointSecret     The Stripe webhook endpoint secret
     */
    public function __construct(
        private RuntimeEnvironment $runtimeEnvironment,
        private StripeClient $stripe,
        private string $endpointSecret,
    ) {}

    /**
     * Verify a Stripe webhook request.
     *
     * @param string $payload   The raw payload from the webhook
     * @param string $sigHeader The Stripe signature header
     *
     * @return bool True if the request is verified, false otherwise
     */
    public function verifyRequest(string $payload, string $sigHeader): bool
    {
        try
        {
            if ($this->runtimeEnvironment->isProduction())
            {
                $event = Webhook::constructEvent(
                    $payload,
                    $sigHeader,
                    $this->endpointSecret,
                );
            }
            else
            {
                // 10 year tolerance for testing
                $event = Webhook::constructEvent(
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

    /**
     * Create a new Stripe customer.
     *
     * @param array<mixed> $data Customer data
     *
     * @return array<mixed> The created customer data
     */
    public function createCustomer(array $data): array
    {
        $customer = Customer::create($data);

        return $customer->toArray();
    }

    /**
     * Retrieve a Stripe customer by ID.
     *
     * @param string $customerId The Stripe customer ID
     *
     * @return array<mixed> The customer data
     */
    public function getCustomerData(string $customerId): array
    {
        $customer = Customer::retrieve(
            [
                'id' => $customerId,
            ]
        );

        return $customer->toArray();
    }

    /**
     * Retrieve invoice data for a given invoice ID.
     *
     * @param string $invoiceId The Stripe invoice ID
     *
     * @return array<mixed> The invoice data
     */
    public function getInvoiceData(string $invoiceId): array
    {
        $invoice = Invoice::retrieve(
            [
                'id' => $invoiceId,
            ]
        );

        return $invoice->toArray();
    }

    /**
     * Create a new Stripe price.
     *
     * @param array<mixed> $data Price data
     *
     * @return array<mixed> The created price data
     */
    public function createPrice(array $data): array
    {
        $price = $this->stripe->prices->create($data);

        return $price->toArray();
    }

    /**
     * Create a new Stripe product.
     *
     * @param array<mixed> $data Product data
     *
     * @return array<mixed> The created product data
     */
    public function createProduct(array $data): array
    {
        $product = $this->stripe->products->create($data);

        return $product->toArray();
    }

    /**
     * Retrieve Stripe products.
     *
     * @param array<mixed> $filter Filter criteria
     *
     * @return array<mixed> The retrieved products
     */
    public function getProductsData(array $filter = []): array
    {
        $products = Product::all();

        $data = $products->toArray();

        return $data['data'];
    }

    /**
     * Cancel a Stripe subscription.
     *
     * @param string $subscriptionId The Stripe subscription ID
     *
     * @return bool True if the subscription was successfully canceled
     */
    public function cancelSubscription(string $subscriptionId): bool
    {
        $subscription = Subscription::retrieve(
            [
                'id' => $subscriptionId,
            ]
        );

        $subscription->cancel();

        return true;
    }

    /**
     * Retrieve a Stripe subscription.
     *
     * @param string        $subscriptionId The Stripe subscription ID
     * @param array<string> $expand         Additional fields to expand
     *
     * @return array<mixed> The subscription data
     */
    public function getSubscriptionData(string $subscriptionId, array $expand = []): array
    {
        $subscription = Subscription::retrieve(
            [
                'id' => $subscriptionId,
                'expand' => $expand,
            ]
        );

        return $subscription->toArray();
    }

    /**
     * Get the enabled webhook events.
     *
     * @return array<string> The enabled webhook events
     *
     * @throws \RuntimeException If there isn't exactly one webhook configured
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
