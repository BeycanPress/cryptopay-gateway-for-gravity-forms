<?php

declare(strict_types=1);

namespace BeycanPress\CryptoPay\Integrator;

use BeycanPress\CryptoPay\Loader;
use BeycanPress\CryptoPay\PluginHero\Hook;
use BeycanPress\CryptoPay\Helpers as ProHelpers;
use BeycanPress\CryptoPay\Pages\TransactionPage;
use BeycanPress\CryptoPay\PluginHero\Http\Response;
// Lite
use BeycanPress\CryptoPayLite\Loader as LiteLoader;
use BeycanPress\CryptoPayLite\Helpers as LiteHelpers;
use BeycanPress\CryptoPayLite\PluginHero\Hook as LiteHook;
use BeycanPress\CryptoPayLite\PluginHero\Http\Response as LiteResponse;
use BeycanPress\CryptoPayLite\Pages\TransactionPage as LiteTransactionPage;

class Helpers
{
    /**
     * @return bool
     */
    public static function bothExists(): bool
    {
        return static::exists() || static::liteExists();
    }

    /**
     * @return bool
     */
    public static function liteExists(): bool
    {
        return class_exists(LiteLoader::class);
    }

    /**
     * @return bool
     */
    public static function exists(): bool
    {
        return class_exists(Loader::class);
    }

    /**
     * @param string $addon
     * @return void
     */
    public static function registerIntegration(string $addon): void
    {
        if (self::exists()) {
            ProHelpers::registerIntegration($addon);
        }

        if (self::liteExists()) {
            LiteHelpers::registerIntegration($addon);
        }
    }

    /**
     * @param array<mixed> ...$args
     * @return void
     */
    // phpcs:ignore
    public static function createTransactionPage(...$args): void
    {
        if (is_admin()) {
            if (self::exists()) {
                new TransactionPage(...$args);
            }

            if (self::liteExists()) {
                new LiteTransactionPage(...$args);
            }
        }
    }

    /**
     * @param string $modelClass
     * @return void
     */
    public static function registerModel(string $modelClass): void
    {
        if (self::exists() && class_exists($modelClass)) {
            $model = new $modelClass();
            Hook::addFilter('models', function (array $models) use ($model): array {
                return array_merge($models, [
                    $model->addon => $model
                ]);
            });
        }
    }

    /**
     * @param string $modelClass
     * @return void
     */
    public static function registerLiteModel(string $modelClass): void
    {
        if (self::liteExists() && class_exists($modelClass)) {
            $model = new $modelClass();
            LiteHook::addFilter('models', function (array $models) use ($model): array {
                return array_merge($models, [
                    $model->addon => $model
                ]);
            });
        }
    }

    /**
     * @param string $method
     * @param array<mixed> ...$args
     * @return mixed
     */
    // phpcs:ignore
    public static function run(string $method, ...$args): mixed
    {
        if (self::exists()) {
            return ProHelpers::$method(...$args);
        } else {
            return LiteHelpers::$method(...$args);
        }
    }

    /**
     * @param string $method
     * @param array<mixed> ...$args
     * @return mixed
     */
    // phpcs:ignore
    public static function response(string $method, ...$args): mixed
    {
        if (self::exists()) {
            return Response::$method(...$args);
        } else {
            return LiteResponse::$method(...$args);
        }
    }
}
