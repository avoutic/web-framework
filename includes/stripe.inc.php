<?php
class StripeFactory extends FactoryCore
{
    protected $config = null;
    private $event_handlers = array();

    function __construct()
    {
        parent::__construct();

        $this->config = $this->get_auth_config('stripe');
        $this->verify(isset($this->config['api_key']), 'Stripe API Key missing');
        $this->verify(isset($this->config['endpoint_secret']), 'Stripe Endpoint Secret missing');

        \Stripe\Stripe::setApiKey($this->config['api_key']);
    }

    function verify_request($payload, $sig_header)
    {
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $this->config['endpoint_secret'],
            );
        } catch(\UnexpectedValueException $e) {
            return false;
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            return false;
        }

        return true;
    }

    function handle_event($payload)
    {
        $event_type = $payload['type'];
        $object = $payload['data']['object'];

        if (!isset($this->event_handlers[$event_type]))
            return 'unhandled-event-type';

        $handler_function = $this->event_handlers[$event_type];

        return $this->$handler_function($object);
    }

    protected function add_event_handler($event_type, $handler_function)
    {
        $this->event_handlers[$event_type] = $handler_function;
    }

    // Customer object
    //
    function create_customer($data)
    {
        $customer = \Stripe\Customer::create($data);

        return $customer->toArray(true);
    }

    function get_customer_data($customer_id)
    {
        $customer = \Stripe\Customer::retrieve(
            array(
                "id" => $customer_id,
            )
        );
        return $customer->toArray(true);
    }

    // Invoice object
    //
    function get_invoice_data($invoice_id)
    {
        $invoice = \Stripe\Invoice::retrieve(
            array(
                "id" => $invoice_id,
            )
        );
        return $invoice->toArray(true);
    }

    // Product object
    //
    function get_products_data($filter = array())
    {
        $products = \Stripe\Product::all();

        $data = $products->toArray(true);

        return $data['data'];
    }

    // Subscription object
    //
    function get_subscription_data($subscription_id, $expand = array())
    {
        $subscription = \Stripe\Subscription::retrieve(
            array(
                "id" => $subscription_id,
                "expand" => $expand,
            )
        );
        return $subscription->toArray(true);
    }

    function cancel_subscription($subscription_id)
    {
            $subscription = \Stripe\Subscription::retrieve(
                array(
                    "id" => $subscription_id,
                )
            );
            if ($subscription === false)
                return false;

            $subscription->cancel();

            return true;
    }

};
?>
