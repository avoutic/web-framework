<?php

namespace WebFramework\Core;

use Psr\Container\ContainerInterface as Container;
use Stripe\StripeClient;

class StripeFactory
{
    /**
     * @var array<string>
     */
    private array $eventHandlers = [];

    public function __construct(
        protected Container $container,
        protected AssertService $assertService,
        protected ConfigService $configService,
        protected StripeClient $stripe,
        protected string $endpointSecret,
    ) {
        $this->init();
    }

    public function init(): void
    {
    }

    public function verifyRequest(string $payload, string $sigHeader): bool
    {
        try
        {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $this->endpointSecret,
            );
        }
        catch (\UnexpectedValueException $e)
        {
            return false;
        }
        catch (\Stripe\Exception\SignatureVerificationException $e)
        {
            return false;
        }

        return true;
    }

    /**
     * @param array<mixed> $payload
     */
    public function handleEvent(array $payload): bool|string
    {
        $eventType = $payload['type'];
        $object = $payload['data']['object'];

        if (!isset($this->eventHandlers[$eventType]))
        {
            return 'unhandled-event-type';
        }

        $handlerFunction = $this->eventHandlers[$eventType];

        return $this->{$handlerFunction}($object);
    }

    protected function addEventHandler(string $eventType, string $handlerFunction): void
    {
        $this->eventHandlers[$eventType] = $handlerFunction;
    }

    /**
     * @return array<string>
     */
    public function getHandledEvents(): array
    {
        return array_keys($this->eventHandlers);
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
        $this->assertService->verify(count($webhooks) == 1, 'Not exactly 1 webhook in place');

        return $webhooks->data[0]->enabled_events;
    }
}
