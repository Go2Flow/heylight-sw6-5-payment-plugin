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

    public static function mapStatus(string $status): string
    {
        if (array_key_exists(strtolower($status), self::STATUS_MAP)) {
            return self::STATUS_MAP[strtolower($status)];
        }
        return self::DECLINED;
    }

}
