<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

// @phpcs:disable PSR1.Files.SideEffects
// @phpcs:disable PSR12.Files.FileHeader
// @phpcs:disable Generic.Files.InlineHTML
// @phpcs:disable Generic.Files.LineLength

/**
 * Plugin Name: Gravity Forms - CryptoPay Gateway
 * Version:     1.0.0
 * Plugin URI:  https://beycanpress.com/cryptopay/
 * Description: Adds Cryptocurrency payment gateway (CryptoPay) for Gravity Forms.
 * Author:      BeycanPress LLC
 * Author URI:  https://beycanpress.com
 * License:     GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: md-cryptopay
 * Tags: Cryptopay, Cryptocurrency, WooCommerce, WordPress, MetaMask, Trust, Binance, Wallet, Ethereum, Bitcoin, Binance smart chain, Payment, Plugin, Gateway, Moralis, Converter, API, coin market cap, CMC
 * Requires at least: 5.0
 * Tested up to: 6.4.3
 * Requires PHP: 8.1
*/

// Autoload
require_once __DIR__ . '/vendor/autoload.php';

use BeycanPress\CryptoPay\Integrator\Helpers;

define('GF_CRYPTOPAY_FILE', __FILE__);
define('GF_CRYPTOPAY_VERSION', '1.0.0');
define('GF_CRYPTOPAY_URL', plugin_dir_url(__FILE__));
define('GF_CRYPTOPAY_DIR', plugin_dir_path(__FILE__));

/**
 * @return void
 */
function gf_cryptopay_addModels(): void
{
    Helpers::registerModel(BeycanPress\CryptoPay\GravityForms\Models\TransactionsPro::class);
    Helpers::registerLiteModel(BeycanPress\CryptoPay\GravityForms\Models\TransactionsLite::class);
}

gf_cryptopay_addModels();

add_action('plugins_loaded', function (): void {

    gf_cryptopay_addModels();

    load_plugin_textdomain('md-cryptopay', false, basename(__DIR__) . '/languages');

    if (!defined('GF_MIN_WP_VERSION')) {
        add_action('admin_notices', function (): void {
            ?>
                <div class="notice notice-error">
                    <p><?php echo sprintf(esc_html__('Gravity Forms - CryptoPay Gateway: This plugin requires Gravity Forms to work. You can buy Gravity Forms by %s.', 'md-cryptopay'), '<a href="https://www.gravityforms.com/" target="_blank">' . esc_html__('clicking here', 'md-cryptopay') . '</a>'); ?></p>
                </div>
            <?php
        });
        return;
    }

    if (Helpers::bothExists()) {
        new BeycanPress\CryptoPay\GravityForms\Loader();
    } else {
        add_action('admin_notices', function (): void {
            ?>
                <div class="notice notice-error">
                    <p><?php echo sprintf(esc_html__('Gravity Forms - CryptoPay Gateway: This plugin is an extra feature plugin so it cannot do anything on its own. It needs CryptoPay to work. You can buy CryptoPay by %s.', 'md-cryptopay'), '<a href="https://beycanpress.com/product/cryptopay-all-in-one-cryptocurrency-payments-for-wordpress/?utm_source=wp_org_addons&utm_medium=gravity_forms" target="_blank">' . esc_html__('clicking here', 'md-cryptopay') . '</a>'); ?></p>
                </div>
            <?php
        });
    }
});
