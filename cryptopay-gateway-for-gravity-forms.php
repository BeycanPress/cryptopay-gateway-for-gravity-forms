<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

// @phpcs:disable PSR1.Files.SideEffects
// @phpcs:disable PSR12.Files.FileHeader
// @phpcs:disable Generic.Files.InlineHTML
// @phpcs:disable Generic.Files.LineLength

/**
 * Plugin Name: CryptoPay Gateway for Gravity Forms
 * Version:     1.0.1
 * Plugin URI:  https://beycanpress.com/cryptopay/
 * Description: Adds Cryptocurrency payment gateway (CryptoPay) for Gravity Forms.
 * Author:      BeycanPress LLC
 * Author URI:  https://beycanpress.com
 * License:     GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: cryptopay-gateway-for-gravity-forms
 * Tags: Bitcoin, Ethereum, Cryptocurrency, Payments, Gravity Forms
 * Requires at least: 5.0
 * Tested up to: 6.7.1
 * Requires PHP: 8.1
*/

// Autoload
require_once __DIR__ . '/vendor/autoload.php';

define('GF_CRYPTOPAY_FILE', __FILE__);
define('GF_CRYPTOPAY_VERSION', '1.0.1');
define('GF_CRYPTOPAY_KEY', basename(__DIR__));
define('GF_CRYPTOPAY_URL', plugin_dir_url(__FILE__));
define('GF_CRYPTOPAY_DIR', plugin_dir_path(__FILE__));
define('GF_CRYPTOPAY_SLUG', plugin_basename(__FILE__));

use BeycanPress\CryptoPay\Integrator\Helpers;
use BeycanPress\CryptoPay\GravityForms\Loader;

/**
 * @return void
 */
function gfCryptoPayRegisterModels(): void
{
    Helpers::registerModel(BeycanPress\CryptoPay\GravityForms\Models\TransactionsPro::class);
    Helpers::registerLiteModel(BeycanPress\CryptoPay\GravityForms\Models\TransactionsLite::class);
}

gfCryptoPayRegisterModels();

add_action('gform_loaded', [Loader::class, 'register'], 5);

add_action('plugins_loaded', function (): void {
    gfCryptoPayRegisterModels();

    if (!defined('GF_MIN_WP_VERSION')) {
        Helpers::requirePluginMessage('Gravity Forms', 'https://www.gravityforms.com/', false);
    } elseif (Helpers::bothExists()) {
        new Loader();
    } else {
        Helpers::requireCryptoPayMessage('Gravity Forms');
    }
});
