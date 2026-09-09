<?php

namespace Go2FlowHeyLightPayment\Helper;

use Go2FlowHeyLightPayment\Service\HeyLightApiService;

class Transaction {

    const CONFIRMED = 'confirmed';
    const INITIATED = 'initiated';
    const WAITING = 'waiting';
    const AUTHORIZED = 'authorized';
    const RESERVED = 'reserved';
    const CANCELLED = 'cancelled';
    const REFUNDED = 'refunded';
    const DISPUTED = 'disputed';
    const DECLINED = 'declined';
    const ERROR = 'error';
    const EXPIRED = 'expired';
    const PARTIALLY_REFUNDED = 'partially-refunded';
    const REFUND_PENDING = 'refund_pending';
    const INSECURE = 'insecure';
    const UNCAPTURED = 'uncaptured';

    const STATUS_MAP = [
        HeyLightApiService::STATUS_APPROVED => Transaction::CONFIRMED,
        HeyLightApiService::STATUS_DECLINED => Transaction::DECLINED,
        HeyLightApiService::STATUS_PENDING => Transaction::WAITING,
        HeyLightApiService::STATUS_AWAITING => Transaction::WAITING,
        HeyLightApiService::STATUS_SUCCESS => Transaction::CONFIRMED,
        HeyLightApiService::STATUS_ACTIVE => Transaction::CONFIRMED,
        HeyLightApiService::STATUS_CANCELLED => Transaction::CANCELLED,
    ];

    /**
     * Maps a HeyLight status string to an internal transaction status.
     *
     * Returns null for any status not present in STATUS_MAP instead of
     * defaulting to DECLINED. An unmapped status must never be treated as a
     * hard decline, since that would erroneously cancel the Shopware
     * transaction for a status HeyLight added/renamed that we don't know
     * about yet. Callers MUST check for null and, if it occurs, log it and
     * leave the transaction untouched so it is retried on the next
     * scheduled sync run instead of being wrongly cancelled.
     */
    public static function mapStatus(string $status): ?string
    {
        return self::STATUS_MAP[strtolower($status)] ?? null;
    }

}
