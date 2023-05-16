<?php

namespace WebFramework\Core;

use Stripe\StripeClient;

class StripeFactory
{
    /**
     * @var array<string>
     */
    private array $event_handlers = [];

    public function __construct(
        protected AssertService $assert_service,
        protected StripeClient $stripe,
        protected string $api_key,
        protected string $endpoint_secret,
    ) {
    }

    public function verify_request(string $payload, string $sig_header): bool
    {
        try
        {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $this->endpoint_secret,
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
    public function handle_event(array $payload): bool|string
    {
        $event_type = $payload['type'];
        $object = $payload['data']['object'];

        if (!isset($this->event_handlers[$event_type]))
        {
            return 'unhandled-event-type';
        }

        $handler_function = $this->event_handlers[$event_type];

        return $this->{$handler_function}($object);
    }

    protected function add_event_handler(string $event_type, string $handler_function): void
    {
        $this->event_handlers[$event_type] = $handler_function;
    }

    /**
     * @return array<string>
     */
    public function get_handled_events(): array
    {
        return array_keys($this->event_handlers);
    }

    // Customer object
    //
    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    public function create_customer(array $data): array
    {
        $customer = \Stripe\Customer::create($data);

        return $customer->toArray();
    }

    /**
     * @return array<mixed>
     */
    public function get_customer_data(string $customer_id): array
    {
        $customer = \Stripe\Customer::retrieve(
            [
                'id' => $customer_id,
            ]
        );

        return $customer->toArray();
    }

    // Invoice object
    //
    /**
     * @return array<mixed>
     */
    public function get_invoice_data(string $invoice_id): array
    {
        $invoice = \Stripe\Invoice::retrieve(
            [
                'id' => $invoice_id,
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
    public function create_price(array $data): array
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
    public function create_product(array $data): array
    {
        $product = $this->stripe->products->create($data);

        return $product->toArray();
    }

    /**
     * @param array<mixed> $filter
     *
     * @return array<mixed>
     */
    public function get_products_data(array $filter = []): array
    {
        $products = \Stripe\Product::all();

        $data = $products->toArray();

        return $data['data'];
    }

    // Subscription object
    //
    public function cancel_subscription(string $subscription_id): bool
    {
        $subscription = \Stripe\Subscription::retrieve(
            [
                'id' => $subscription_id,
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
    public function get_subscription_data(string $subscription_id, array $expand = []): array
    {
        $subscription = \Stripe\Subscription::retrieve(
            [
                'id' => $subscription_id,
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
    public function get_webhook_events(): array
    {
        $webhooks = $this->stripe->webhookEndpoints->all();
        $this->assert_service->verify(count($webhooks) == 1, 'Not exactly 1 webhook in place');

        return $webhooks->data[0]->enabled_events;
    }
}
