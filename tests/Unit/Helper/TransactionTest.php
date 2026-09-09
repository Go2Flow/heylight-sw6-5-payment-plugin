<?php

declare(strict_types=1);

namespace Go2FlowHeyLightPayment\Tests\Unit\Helper;

use Go2FlowHeyLightPayment\Helper\Transaction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function knownStatusProvider(): array
    {
        return [
            'approved -> confirmed'             => ['performing', Transaction::CONFIRMED],
            'declined -> declined'              => ['abandoned', Transaction::DECLINED],
            'pending -> waiting'                => ['pending', Transaction::WAITING],
            'awaiting_confirmation -> waiting'   => ['awaiting_confirmation', Transaction::WAITING],
            'success -> confirmed'              => ['success', Transaction::CONFIRMED],
            'active -> confirmed'               => ['active', Transaction::CONFIRMED],
            'cancelled -> cancelled'            => ['cancelled', Transaction::CANCELLED],
        ];
    }

    #[DataProvider('knownStatusProvider')]
    public function testMapStatusMapsKnownHeyLightStatuses(string $heyLightStatus, string $expected): void
    {
        self::assertSame($expected, Transaction::mapStatus($heyLightStatus));
    }

    public function testMapStatusIsCaseInsensitive(): void
    {
        self::assertSame(Transaction::CONFIRMED, Transaction::mapStatus('PERFORMING'));
        self::assertSame(Transaction::WAITING, Transaction::mapStatus('Awaiting_Confirmation'));
    }

    public function testMapStatusReturnsNullForUnknownStatus(): void
    {
        // Regression guard: an unknown/renamed HeyLight status must map to
        // null, never silently fall back to DECLINED, since that would
        // wrongly cancel the Shopware transaction.
        self::assertNull(Transaction::mapStatus('some_future_status_we_do_not_know_yet'));
    }

    public function testMapStatusReturnsNullForEmptyString(): void
    {
        self::assertNull(Transaction::mapStatus(''));
    }
}
