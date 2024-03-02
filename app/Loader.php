<?php

declare(strict_types=1);

namespace BeycanPress\CryptoPay\GravityForms;

use BeycanPress\CryptoPay\Integrator\Hook;
use BeycanPress\CryptoPay\Integrator\Helpers;

class Loader
{
    /**
     * Loader constructor.
     */
    public function __construct()
    {
        add_action('gform_loaded', [$this, 'register'], 5);

        Helpers::registerIntegration('gravityforms');
        Helpers::createTransactionPage(
            esc_html__('GravityForms transactions', 'gf-cryptopay'),
            'gravityforms',
            10,
            [
                'orderId' => function ($tx) {
                    return Helpers::run('view', 'components/link', [
                        'url' => sprintf(admin_url('admin.php?page=gf_entries&view=entry&id=%d&lid=%d&order=ASC&filter&paged=1&pos=0&field_id&operator'), $tx->params->formId, $tx->orderId), // @phpcs:ignore
                        'text' => sprintf(esc_html__('View entry #%d', 'gf-cryptopay'), $tx->orderId)
                    ]);
                }
            ],
        );

        Hook::addFilter('payment_redirect_urls_gravityforms', [$this, 'paymentRedirectUrls']);
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
        if (Helpers::exists()) {
            \GF_Fields::register(new Gateways\CryptoPayLite());
        } else {
            \GF_Fields::register(new Gateways\CryptoPayLite());
        }
    }
}
