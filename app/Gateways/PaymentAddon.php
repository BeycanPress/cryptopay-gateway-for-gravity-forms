<?php

declare(strict_types=1);

namespace BeycanPress\CryptoPay\GravityForms\Gateways;

// @phpcs:disable WordPress.Security.NonceVerification.Missing
// @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

use BeycanPress\CryptoPay\Integrator\Helpers;

class PaymentAddon extends \GFPaymentAddOn
{
    /**
     * @var string
     */
    // @phpcs:ignore
    protected $_version = GF_CRYPTOPAY_VERSION;

    /**
     * @var string
     */
    // @phpcs:ignore
    protected $_min_gravityforms_version = "1.8.12";

    /**
     * @var string
     */
    // @phpcs:ignore
    protected $_slug = GF_CRYPTOPAY_KEY;

    /**
     * @var string
     */
    // @phpcs:ignore
    protected $_path = GF_CRYPTOPAY_SLUG;

    /**
     * @var string
     */
    // @phpcs:ignore
    protected $_full_path = GF_CRYPTOPAY_FILE;

    /**
     * @var string
     */
    // @phpcs:ignore
    protected $_title = 'Adds cryptocurrency payments to your form.';

    /**
     * @var string
     */
    // @phpcs:ignore
    protected $_short_title = 'CryptoPay';

    /**
     * @var string
     */
    // @phpcs:ignore
    protected $_supports_callbacks = false;

    /**
     * @var string
     */
    // @phpcs:ignore
    protected $_requires_credit_card = false;

    /**
     * @var self|null
     */
    // @phpcs:ignore
    private static $_instance = null;

    /**
     * @return self
     */
    // @phpcs:ignore
    public static function get_instance(): self
    {
        return self::$_instance ??= new self();
    }

    /**
     * @return array<string,mixed>
     */
    public function feed_list_columns(): array
    {
        return [
            'feedName' => esc_html__('Name', 'cryptopay-gateway-for-gravity-forms'),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function feed_settings_fields(): array
    {
        return [
            [
                'description' => '',
                'fields'      => [
                    [
                        'name'     => 'feedName',
                        'label'    => esc_html__('Name', 'cryptopay-gateway-for-gravity-forms'),
                        'type'     => 'text',
                        'class'    => 'medium',
                        'required' => true,
                        'tooltip'  => '<h6>' . esc_html__('Name', 'gravityforms') . '</h6>' . esc_html__('Enter a feed name to uniquely identify this setup.', 'cryptopay-gateway-for-gravity-forms') // phpcs:ignore
                    ],
                ]
            ]
        ];
    }

    /**
     * @return void
     */
    public function init_frontend(): void
    {
        parent::init_frontend();
        add_filter("gform_{$this->_slug}_pre_process_feeds", [$this, 'pre_process_feeds'], 10, 3);
    }
    /**
     * @param array<string,mixed> $feeds
     * @param array<string,mixed> $entry
     * @param array<string,mixed> $form
     * @return array<string,mixed>
     */
    // @phpcs:ignore
    public function pre_process_feeds($feeds, $entry, $form): array
    {
        $ourFeedIndex = array_search($this->_slug, array_column($feeds, 'addon_slug'), true);
        $feeds[$ourFeedIndex]['meta']['version'] = Helpers::exists() ? 'cryptopay' : 'cryptopay_lite';
        $feeds[$ourFeedIndex]['meta']['transactionType'] = 'product';
        return $feeds;
    }

    /**
     * @param array<string,mixed> $feed
     * @param array<string,mixed> $data
     * @param array<string,mixed> $form
     * @param array<string,mixed> $entry
     * @return array<string,mixed>
     */
    // @phpcs:ignore
    public function authorize($feed, $data, $form, $entry): array
    {
        $msg = '';
        $auth = true;
        $type = $feed['meta']['version'];
        $ourFieldIndex = array_search($type, array_column($form['fields'], 'type'));
        $ourField = $ourFieldIndex ? $form['fields'][$ourFieldIndex] : null;
        if (!$ourField) {
            return [
                'is_authorized' => true,
                'error_message' => esc_html__('The payment field is not found!', 'cryptopay-gateway-for-gravity-forms')
            ];
        }

        // In Gravity Forms process already have nonce process
        $txId = $ourField ? sanitize_text_field(wp_unslash($_POST[$ourField->field_input_id] ?? '')) : '';
        $tx = Helpers::run('getModelByAddon', 'gravityforms')->findOneBy(['hash' => $txId]);

        if (!$tx) {
            $auth = false;
            $msg = esc_html__('A transaction was not found, please complete the payment process!', 'cryptopay-gateway-for-gravity-forms'); // phpcs:ignore
        }

        if ($tx && 'verified' != $tx->getStatus()->getValue()) {
            $auth = false;
            $msg = esc_html__('The transaction is not verified yet, please wait for the confirmation!', 'cryptopay-gateway-for-gravity-forms'); // phpcs:ignore
        }

        return [
            'error_message' => $msg,
            'is_authorized' => $auth,
            'transaction_id' => $txId
        ];
    }

    /**
     * @param array<string,mixed> $auth
     * @param array<string,mixed> $feed
     * @param array<string,mixed> $data
     * @param array<string,mixed> $form
     * @param array<string,mixed> $entry
     * @return array<string,mixed>
     */
    // @phpcs:ignore
    public function capture($auth, $feed, $data, $form, $entry): array
    {
        if (!isset($auth['transaction_id'])) {
            return [
                'is_success' => false,
                'error_message' => $auth['error_message'] ?? esc_html__(
                    'The transaction id is not found!',
                    'cryptopay-gateway-for-gravity-forms'
                )
            ];
        }
        return [
            'is_success' => true,
            'amount' => $data['payment_amount'],
            'payment_method' => $this->_short_title,
            'transaction_id' => $auth['transaction_id'],
        ];
    }

    /**
     * @return string
     */
    public function get_menu_icon(): string
    {
        $file = GF_CRYPTOPAY_DIR . 'assets/images/icon.svg';

        if (!\is_readable($file)) {
            throw new \Exception(
                \sprintf(
                    'Could not read WordPress admin menu icon from file: %s.',
                    esc_html($file)
                )
            );
        }

        $svg = \file_get_contents($file, true);

        if (false === $svg) {
            throw new \Exception(
                \sprintf(
                    'Could not read WordPress admin menu icon from file: %s.',
                    esc_html($file)
                )
            );
        }

        return $svg;
    }
}
