# WooCommerce Colibrix Gateway Payment

WordPress plugin for WooCommerce that adds payments via Colibrix Gateway.

The plugin registers two payment methods:

- `Colibrix Gateway - Card` for bank card payments
- `Colibrix Gateway - APM` for alternative payment methods

## Main functionality

- creates a payment in Colibrix Gateway during checkout
- redirects the customer to the external payment page
- receives webhook notifications from Colibrix and updates the WooCommerce order status
- returns the customer back to the store after payment
- saves `tracking_id` and `payment UID` in order meta
- shows payment data in the WooCommerce admin area
- supports refunds via API for the card payment method
- supports WooCommerce HPOS
- supports WooCommerce Cart & Checkout Blocks
- writes debug logs for API requests and responses into WooCommerce logs

## Supported payment methods

### 1. Card

### 2. APM

## Payment flow

1. The customer selects a Colibrix payment method at checkout.
2. The plugin builds a payload and sends it to the Colibrix API.
3. WooCommerce moves the order to `on-hold` if the payment is not confirmed yet.
4. The customer is redirected to the Colibrix payment page.
5. After payment, Colibrix sends a webhook to WooCommerce.
6. The plugin verifies the `X-Signature`, finds the order, and updates its status:
   - `success` or `settlement in progress` -> the order is marked as paid
   - `failed` -> the order is moved to `failed`
   - `pending` or `new` -> the order remains `on-hold`

## WooCommerce settings

Each payment method has the following settings:

- `Enable/Disable`
- `Title`
- `Description`
- `API Base URL`
- `Project ID`
- `API Key`
- `Signature Key`
- `Test mode`
- `Debug log`

`Colibrix Gateway - APM` has one more setting:

- `Pre-selected bank` - optional. The plugin sends the value as `bank_name`, and the Colibrix payment page opens with this bank selected. Enter the bank id, for example `revolut_eu`. Ask Colibrix for the bank ids of your shop, and make one test payment after you change the value. Leave the setting empty to show the bank list. If the id matches no bank, the payment page shows the bank list.

Important: the code uses a development `API Base URL` by default. For production, replace it in the WooCommerce settings.

## Requirements

- WordPress 5.8+
- PHP 7.4+
- WooCommerce 6.0+

## Installation

1. Copy the plugin into `wp-content/plugins/wc-colibrix-gateway-payment`.
2. Activate the plugin in WordPress Admin.
3. Go to `WooCommerce -> Settings -> Payments`.
4. Configure `Colibrix Gateway - Card` and/or `Colibrix Gateway - APM`.
5. Enter your live or test Colibrix API credentials.

## Data sent to the API

The plugin sends:

- `project_id`
- order amount in minor units
- currency
- order description
- `tracking_id` in the format `wc-order-{order_id}`
- notification URL
- customer return URL
- customer data from WooCommerce billing fields
- `bank_name` for APM payments, when `Pre-selected bank` has a value

## Project structure

- `wc-colibrix-gateway-payment.php` - plugin bootstrap
- `includes/class-wc-colibrix-gateway-plugin.php` - gateway registration and block support
- `includes/gateways/abstract-wc-colibrix-gateway.php` - shared API, webhook, and status logic
- `includes/gateways/class-wc-colibrix-gateway-card.php` - card gateway implementation
- `includes/gateways/class-wc-colibrix-gateway-apm.php` - APM gateway implementation
- `includes/blocks/class-wc-colibrix-gateway-blocks-support.php` - WooCommerce Blocks integration
- `assets/js/frontend/blocks.js` - payment method registration for checkout blocks

## Tests

The tests run without WordPress and WooCommerce. They use stub classes from `tests/stubs.php`.

```bash
composer install
composer test
```

## Logs and debugging

If `Debug log` is enabled, the plugin writes the following into WooCommerce logs:

- outgoing request payloads
- masked headers
- API raw/body responses
- callback/webhook processing data

Logs are available in `WooCommerce -> Status -> Logs`.
