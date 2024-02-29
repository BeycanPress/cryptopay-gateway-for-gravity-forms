<?php

declare(strict_types=1);

namespace BeycanPress\CryptoPay\GravityForms\Models;

use BeycanPress\CryptoPayLite\Models\AbstractTransaction;

class TransactionsLite extends AbstractTransaction
{
    public string $addon = 'gravity_forms';

    /**
     * @return void
     */
    public function __construct()
    {
        parent::__construct('gravity_forms_transaction');
    }
}
