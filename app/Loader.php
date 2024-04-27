<?php

declare(strict_types=1);

namespace BeycanPress\CryptoPay\GravityForms;

// @phpcs:disable Generic.Files.InlineHTML

use BeycanPress\CryptoPay\Integrator\Hook;
use BeycanPress\CryptoPay\Integrator\Helpers;
use BeycanPress\CryptoPay\Pages\TransactionPage;
use BeycanPress\CryptoPayLite\Pages\TransactionPage as TransactionPageLite;

class Loader
{
    /**
     * Loader constructor.
     */
    public function __construct()
    {
        $this->registerTransactionListPages();
        Helpers::registerIntegration('gravityforms');
        add_action('gform_loaded', [$this, 'register'], 5);
        Hook::addFilter('payment_redirect_urls_gravityforms', [$this, 'paymentRedirectUrls']);
        add_action('gform_field_standard_settings', [ $this, 'fieldStandardSettings' ], 10, 2);
    }

    /**
     * @return void
     */
    public function registerTransactionListPages(): void
    {
        if (!is_admin()) {
            return;
        }

        $args = [
            esc_html__('GravityForms transactions', 'gf-cryptopay'),
            'gravityforms',
            10,
            [
                'orderId' => function ($tx) {
                    if (!isset($tx->orderId)) {
                        return esc_html__('Pending...', 'gf-cryptopay');
                    }

                    $formId = $tx->params?->formId ?? $tx->params?->formIdOld;
                    return Helpers::run('view', 'components/link', [
                        'url' => sprintf(admin_url('admin.php?page=gf_entries&view=entry&id=%d&lid=%d&order=ASC&filter&paged=1&pos=0&field_id&operator'), $formId, $tx->orderId), // @phpcs:ignore
                        /* translators: %d: transaction id */
                        'text' => sprintf(esc_html__('View entry #%d', 'gf-cryptopay'), $tx->orderId)
                    ]);
                }
            ],
        ];

        if (Helpers::exists()) {
            new TransactionPage(...$args);
        } else {
            new TransactionPageLite(...$args);
        }
    }

    /**
     * @param int $position
     * @param int $formId
     * @return void
     */
    // @phpcs:ignore
    public function fieldStandardSettings($position, $formId): void
    {
        if (0 !== $position) {
            return;
        }
        ?>
            <li class="cryptopay_field_setting field_setting">
                <label for="field_cryptopay_theme">
                    <?php esc_html_e('Choose a theme', 'gf-cryptopay'); ?>
                </label>
                <select 
                    name="field_cryptopay_theme" 
                    id="field_cryptopay_theme" 
                    onchange="SetFieldProperty('theme', this.value);"
                >
                    <option value="light">Light</option>
                    <option value="dark">Dark</option>
                </select>
            </li>
        <?php
    }

    /**
     * Payment redirect urls
     * @param object $data
     * @return array<string,string>
     */
    public function paymentRedirectUrls(object $data): array
    {
        $formId = $data->getParams()->get('formId');
        return [
            'success' => '#gform_wrapper_' . $formId,
            'failed' => '#gform_wrapper_' . $formId
        ];
    }

    /**
     * @return void
     */
    public function register(): void
    {
        \GFForms::include_payment_addon_framework();
        \GFAddOn::register(Gateways\PaymentAddon::class);

        if (Helpers::exists()) {
            \GF_Fields::register(new Gateways\Gateway());
        } else {
            \GF_Fields::register(new Gateways\GatewayLite());
        }
    }
}
