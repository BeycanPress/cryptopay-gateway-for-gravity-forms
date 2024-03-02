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
            [],
            ['orderId']
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
        return [
            'success' => '#',
            'failed' => 'reload'
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
