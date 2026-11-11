<?php

defined('ABSPATH') || exit;

class WC_Colibrix_Gateway_Apm extends WC_Colibrix_Gateway_Abstract
{
    protected function get_gateway_id(): string
    {
        return 'colibrix_gateway_apm';
    }

    protected function get_gateway_method_title(): string
    {
        return __('Colibrix Gateway - APM', 'wc-colibrix-gateway-payment');
    }

    protected function get_gateway_method_description(): string
    {
        return __('Pay with alternative payment methods (APM) via Colibrix Gateway.', 'wc-colibrix-gateway-payment');
    }

    protected function get_default_checkout_title(): string
    {
        return __('Alternative payment methods', 'wc-colibrix-gateway-payment');
    }

    protected function get_default_checkout_description(): string
    {
        return __('Alternative payment methods via Colibrix Gateway.', 'wc-colibrix-gateway-payment');
    }

    public function init_form_fields(): void
    {
        parent::init_form_fields();

        $this->form_fields['bank_name'] = [
            'title'       => __('Pre-selected bank', 'wc-colibrix-gateway-payment'),
            'type'        => 'text',
            'description' => __('Optional. Bank id that the payment page selects for the shopper, for example revolut_eu. Leave empty to show the bank list.', 'wc-colibrix-gateway-payment'),
            'default'     => '',
        ];
    }

    protected function get_payment_endpoint(): string
    {
        return '/apm/payment';
    }

    protected function get_refund_endpoint(): ?string
    {
        return null;
    }

    protected function build_payment_payload(WC_Order $order, string $tracking_id): array
    {
        $amount = (int) round((float) $order->get_total() * (10 ** wc_get_price_decimals()));

        $payload = [
            'project_id'       => $this->project_id,
            'transaction_type' => 'payment',
            'test'             => $this->test_mode === 'yes',
            'notification_url' => WC()->api_request_url($this->get_notify_api_key()),
            'method'   => [
                'type' => 'bank_transfer',
            ],

            'amount'      => $amount,
            'currency'    => (string) $order->get_currency(),
            'description' => sprintf('Order #%d', $order->get_id()),
            'tracking_id' => $tracking_id,

            'return_url' => $this->build_return_url($order),

            'customer'         => [
                'email'      => (string) $order->get_billing_email(),
                'first_name' => (string) $order->get_billing_first_name(),
                'last_name'  => (string) $order->get_billing_last_name(),
                'country'    => (string) $order->get_billing_country(),
                'city'       => (string) $order->get_billing_city(),
                'state'      => (string) $order->get_billing_state(),
                'zip'        => (string) $order->get_billing_postcode(),
                'address'    => trim((string) $order->get_billing_address_1() . ' ' . (string) $order->get_billing_address_2()),
                'phone'      => (string) $order->get_billing_phone(),
            ],

            'bank_name' => trim((string) $this->get_option('bank_name', '')),
        ];

        return $this->filter_empty($payload);
    }

    protected function get_redirect_url(array $body): string{
        return $body['transaction']['form']['redirect_url'] ?? '';
    }

    protected function get_response_uid(array $body): string
    {
        return $body['transaction']['uid'] ?? '';
    }
}
