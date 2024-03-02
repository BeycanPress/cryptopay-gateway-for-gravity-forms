<?php

declare(strict_types=1);

namespace BeycanPress\CryptoPay\GravityForms\Gateways;

// @phpcs:disable Generic.Files.InlineHTML
// @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

use BeycanPress\CryptoPayLite\Payment;

class GatewayLite extends AbstractGateway
{
    /**
     * @var string
     */
    // @phpcs:ignore
    public $type = 'cryptopay_lite';

    /**
     * @param array<mixed> $properties
     */
    public function __construct(array $properties = [])
    {
        parent::__construct($properties);
    }

    /**
     * @return string
     */
    public function get_form_editor_field_title(): string
    {
        return esc_attr__('CryptoPay Lite', 'gf-cryptopay');
    }

    /**
     * @return string
     */
    public function get_form_editor_field_description(): string
    {
        return esc_attr__('Adds cryptocurrency payments to your form.', 'gf-cryptopay');
    }

    /**
     * @param int $formId
     * @return string
     */
    public function run(int $formId): string
    {
        return (new Payment('gravityforms'))->html();
    }
}
