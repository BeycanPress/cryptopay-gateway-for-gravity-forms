<?php

declare(strict_types=1);

namespace BeycanPress\CryptoPay\GravityForms\Models;

use BeycanPress\CryptoPay\Models\AbstractTransaction;

class TransactionsPro extends AbstractTransaction
{
    public string $addon = 'gravityforms';

    /**
     * @return void
     */
    public function __construct()
    {
        parent::__construct('gravityforms_transaction');
    }

    /**
     * @param int $userId
     * @param string $formId
     * @return object|null
     */
    public function findOneByUserAndFormId(int $userId, string $formId): ?object
    {
        return $this->getRow(str_ireplace(
            ['%d', '%s'],
            [$userId, $formId],
            "SELECT * FROM {$this->tableName} 
            WHERE `userId` = %d
            AND `params` LIKE '%{\"formId\":\"%s\"}%'
            ORDER BY `id` DESC
            LIMIT 1"
        ));
    }

    /**
     * @param string $formId
     * @return void
     */
    public function cleanFormIdsInTxs(string $formId): void
    {
        $this->update(
            [
                'params' => json_encode(['formIdOld' => $formId]),
            ],
            [
                'params' => json_encode(['formId' => $formId]),
            ]
        );
    }

    /**
     * @param string $hash
     * @param int $orderId
     * @return bool
     */
    public function updateOrderIdByTxHash(string $hash, int $orderId): bool
    {
        return (bool) $this->update(
            [
                'orderId' => $orderId,
            ],
            [
                'hash' => $hash,
            ]
        );
    }
}
