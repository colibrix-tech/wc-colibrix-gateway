<?php

use PHPUnit\Framework\TestCase;

final class PreselectedBankTest extends TestCase
{
    private const SIGNATURE_KEY = 'test-signature-key';
    private const SHARED_KEYS = [
        'enabled',
        'title',
        'description',
        'api_base_url',
        'project_id',
        'api_key',
        'signature_key',
        'test_mode',
        'debug_mode',
    ];
    private const BASE_OPTIONS = [
        'api_base_url'  => 'https://mapi.example',
        'project_id'    => '20',
        'api_key'       => 'test-api-key',
        'signature_key' => self::SIGNATURE_KEY,
        'test_mode'     => 'no',
    ];

    protected function setUp(): void
    {
        Colibrix_Test_State::reset();
    }

    public function settings_cases(): array
    {
        return [
            'SCN-woocommerce-preselected-bank-001-1 APM settings contain the field' => [
                WC_Colibrix_Gateway_Apm::class,
                array_merge(self::SHARED_KEYS, ['bank_name']),
            ],
            'SCN-woocommerce-preselected-bank-001-2 Card settings do not contain the field' => [
                WC_Colibrix_Gateway_Card::class,
                self::SHARED_KEYS,
            ],
        ];
    }

    /**
     * @dataProvider settings_cases
     */
    public function test_settings_fields(string $gateway_class, array $expected_keys): void
    {
        $fields = (new $gateway_class())->form_fields;

        $this->assertSame($expected_keys, array_keys($fields));

        if (in_array('bank_name', $expected_keys, true)) {
            $this->assertSame('text', $fields['bank_name']['type']);
            $this->assertSame('Pre-selected bank', $fields['bank_name']['title']);
            $this->assertSame('', $fields['bank_name']['default']);
        }
    }

    public function apm_request_cases(): array
    {
        return [
            'SCN-woocommerce-preselected-bank-001-3 Stored settings from version 1.0.0' => [[], null],
            'SCN-woocommerce-preselected-bank-002-1 Setting with a bank id' => [['bank_name' => 'revolut_eu'], 'revolut_eu'],
            'SCN-woocommerce-preselected-bank-002-2 Empty setting' => [['bank_name' => ''], null],
            'SCN-woocommerce-preselected-bank-002-3 Whitespace-only setting' => [['bank_name' => '   '], null],
            'SCN-woocommerce-preselected-bank-002-4 Whitespace around the bank id' => [['bank_name' => '  wise-live  '], 'wise-live'],
            'SCN-woocommerce-preselected-bank-002-5 Letter case is kept' => [['bank_name' => 'Revolut_EU'], 'Revolut_EU'],
            'SCN-woocommerce-preselected-bank-002-6 Unknown bank id' => [['bank_name' => 'not-a-bank'], 'not-a-bank'],
        ];
    }

    /**
     * @dataProvider apm_request_cases
     */
    public function test_apm_payment_request(array $stored, ?string $expected_bank_name): void
    {
        Colibrix_Test_State::$options['colibrix_gateway_apm'] = array_merge(self::BASE_OPTIONS, $stored);
        Colibrix_Test_State::$orders[1001] = new WC_Order(1001, '25.00', 'EUR');

        $result = (new WC_Colibrix_Gateway_Apm())->process_payment(1001);

        $this->assertSame(['result' => 'success', 'redirect' => 'https://pay.example/hpp/uid-1'], $result);
        $this->assertSame([], Colibrix_Test_State::$notices);
        $this->assertCount(1, Colibrix_Test_State::$requests);

        $request = Colibrix_Test_State::$requests[0];
        $body    = json_decode($request['body'], true);

        $this->assertSame('https://mapi.example/apm/payment', $request['url']);
        $this->assertSame(hash('sha256', $request['body'] . self::SIGNATURE_KEY), $request['headers']['X-Signature']);
        $this->assertMatchesRegularExpression(
            '~^https://shop\.example/\?wc-api=wc_colibrix_gateway_colibrix_gateway_apm_return&order_id=1001&expires=\d+&token=[0-9a-f]{64}$~',
            $body['return_url']
        );

        $expected = [
            'project_id'       => 20,
            'transaction_type' => 'payment',
            'test'             => false,
            'notification_url' => 'https://shop.example/wc-api/wc_colibrix_gateway_colibrix_gateway_apm_notify/',
            'method'           => ['type' => 'bank_transfer'],
            'amount'           => 2500,
            'currency'         => 'EUR',
            'description'      => 'Order #1001',
            'tracking_id'      => 'wc-order-1001',
            'return_url'       => $body['return_url'],
            'customer'         => [
                'email'      => 'shopper@example.com',
                'first_name' => 'John',
                'last_name'  => 'Doe',
                'country'    => 'DE',
                'city'       => 'Berlin',
                'zip'        => '10115',
                'address'    => 'Musterstrasse 1',
                'phone'      => '+491700000000',
            ],
        ];
        if ($expected_bank_name !== null) {
            $expected['bank_name'] = $expected_bank_name;
        }

        $this->assertSame(
            json_encode($expected, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $request['body']
        );
    }

    public function card_request_cases(): array
    {
        return [
            'SCN-woocommerce-preselected-bank-003-1 APM setting has a value' => [['bank_name' => 'revolut_eu'], []],
            'SCN-woocommerce-preselected-bank-003-2 Stored card settings contain a bank_name entry' => [[], ['bank_name' => 'revolut_eu']],
        ];
    }

    /**
     * @dataProvider card_request_cases
     */
    public function test_card_payment_request(array $stored_apm, array $stored_card): void
    {
        Colibrix_Test_State::$options['colibrix_gateway_apm']  = array_merge(self::BASE_OPTIONS, $stored_apm);
        Colibrix_Test_State::$options['colibrix_gateway_card'] = array_merge(self::BASE_OPTIONS, $stored_card);
        Colibrix_Test_State::$orders[1002] = new WC_Order(1002, '25.00', 'EUR');

        $result = (new WC_Colibrix_Gateway_Card())->process_payment(1002);

        $this->assertSame('success', $result['result']);
        $this->assertCount(1, Colibrix_Test_State::$requests);

        $request = Colibrix_Test_State::$requests[0];
        $body    = json_decode($request['body'], true);

        $this->assertSame('https://mapi.example/payment_page', $request['url']);
        $this->assertStringNotContainsString('bank_name', $request['body']);
        $this->assertSame(['main_type' => 'bank_card'], $body['payment_method']);
        $this->assertSame('wc-order-1002', $body['order']['tracking_id']);
        $this->assertSame(2500, $body['order']['amount']);
    }
}
