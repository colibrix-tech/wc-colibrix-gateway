<?php

final class Colibrix_Test_State
{
    public static array $options = [];
    public static array $orders = [];
    public static array $requests = [];
    public static array $notices = [];

    public static function reset(): void
    {
        self::$options  = [];
        self::$orders   = [];
        self::$requests = [];
        self::$notices  = [];
    }
}

class WP_Error
{
}

abstract class WC_Payment_Gateway
{
    public $id = '';
    public $method_title = '';
    public $method_description = '';
    public $has_fields = false;
    public $supports = [];
    public $title = '';
    public $description = '';
    public $enabled = 'no';
    public $form_fields = [];

    public function init_settings(): void
    {
    }

    public function get_option($key, $empty_value = null)
    {
        $stored = Colibrix_Test_State::$options[$this->id] ?? [];
        $value  = array_key_exists($key, $stored)
            ? $stored[$key]
            : ($this->form_fields[$key]['default'] ?? '');

        if ($empty_value !== null && $value === '') {
            return $empty_value;
        }

        return $value;
    }

    public function process_admin_options(): bool
    {
        return true;
    }

    public function get_return_url($order = null): string
    {
        return 'https://shop.example/checkout/order-received/';
    }
}

class WC_Order
{
    private const BILLING = [
        'get_billing_email'      => 'shopper@example.com',
        'get_billing_first_name' => 'John',
        'get_billing_last_name'  => 'Doe',
        'get_billing_country'    => 'DE',
        'get_billing_city'       => 'Berlin',
        'get_billing_state'      => '',
        'get_billing_postcode'   => '10115',
        'get_billing_address_1'  => 'Musterstrasse 1',
        'get_billing_address_2'  => '',
        'get_billing_phone'      => '+491700000000',
    ];

    private int $id;
    private string $total;
    private string $currency;
    private string $status = 'pending';
    private array $meta = [];

    public function __construct(int $id, string $total, string $currency)
    {
        $this->id       = $id;
        $this->total    = $total;
        $this->currency = $currency;
    }

    public function get_id(): int
    {
        return $this->id;
    }

    public function get_total(): string
    {
        return $this->total;
    }

    public function get_currency(): string
    {
        return $this->currency;
    }

    public function get_order_key(): string
    {
        return 'wc_order_key_' . $this->id;
    }

    public function __call(string $name, array $arguments): string
    {
        return self::BILLING[$name];
    }

    public function is_paid(): bool
    {
        return false;
    }

    public function get_status(): string
    {
        return $this->status;
    }

    public function update_status(string $status, string $note = ''): void
    {
        $this->status = $status;
    }

    public function update_meta_data(string $key, $value): void
    {
        $this->meta[$key] = $value;
    }

    public function get_meta(string $key)
    {
        return $this->meta[$key] ?? '';
    }

    public function save(): int
    {
        return $this->id;
    }
}

function WC(): object
{
    return new class {
        public function api_request_url(string $request): string
        {
            return 'https://shop.example/wc-api/' . $request . '/';
        }
    };
}

function __(string $text, string $domain = 'default'): string
{
    return $text;
}

function add_action(string $hook, $callback, int $priority = 10, int $accepted_args = 1): bool
{
    return true;
}

function wc_get_order($order_id)
{
    return Colibrix_Test_State::$orders[(int) $order_id] ?? false;
}

function wc_add_notice(string $message, string $notice_type = 'success'): void
{
    Colibrix_Test_State::$notices[] = ['message' => $message, 'type' => $notice_type];
}

function wc_get_price_decimals(): int
{
    return 2;
}

function wp_json_encode($data, int $options = 0, int $depth = 512)
{
    return json_encode($data, $options, $depth);
}

function wp_remote_post(string $url, array $args = [])
{
    Colibrix_Test_State::$requests[] = [
        'url'     => $url,
        'headers' => $args['headers'] ?? [],
        'body'    => $args['body'] ?? '',
    ];

    return [
        'response' => ['code' => 200],
        'body'     => json_encode([
            'redirect_url' => 'https://pay.example/hpp/uid-1',
            'uid'          => 'uid-1',
            'transaction'  => [
                'uid'  => 'uid-1',
                'form' => ['redirect_url' => 'https://pay.example/hpp/uid-1'],
            ],
        ]),
    ];
}

function is_wp_error($thing): bool
{
    return $thing instanceof WP_Error;
}

function wp_remote_retrieve_body($response): string
{
    return is_array($response) ? (string) ($response['body'] ?? '') : '';
}

function wp_remote_retrieve_response_code($response)
{
    return is_array($response) ? ($response['response']['code'] ?? '') : '';
}

function add_query_arg(array $args, string $url): string
{
    return $url . '?' . http_build_query($args);
}

function home_url(string $path = ''): string
{
    return 'https://shop.example' . $path;
}

function get_locale(): string
{
    return 'en_US';
}

function sanitize_text_field($value): string
{
    return trim((string) $value);
}
