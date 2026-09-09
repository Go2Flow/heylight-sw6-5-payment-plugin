<?php

declare(strict_types=1);

namespace Go2FlowHeyLightPayment\Tests\Unit\Service;

use Go2FlowHeyLightPayment\Service\HeyLightApiService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionClass;

/**
 * Covers only the response-validation logic of getOrderStatus(), extracted
 * into the private HeyLightApiService::parseOrderStatusResponse() method so
 * it can be tested in isolation.
 *
 * The service is built via ReflectionClass::newInstanceWithoutConstructor()
 * so the test needs no real SystemConfigService/WebhookService/Shopware
 * kernel at all - only the "logger" property is populated (via reflection)
 * with a lightweight spy, since parseOrderStatusResponse() logs on the
 * error paths.
 */
class HeyLightApiServiceStatusParsingTest extends TestCase
{
    private function buildService(): array
    {
        $reflection = new ReflectionClass(HeyLightApiService::class);
        $service = $reflection->newInstanceWithoutConstructor();

        $logger = new class extends NullLogger {
            /** @var array<int, array{message: string, context: array}> */
            public array $errors = [];

            public function error($message, array $context = []): void
            {
                $this->errors[] = ['message' => $message, 'context' => $context];
            }
        };

        $loggerProperty = $reflection->getProperty('logger');
        $loggerProperty->setAccessible(true);
        $loggerProperty->setValue($service, $logger);

        return [$service, $logger, $reflection];
    }

    private function invokeParseOrderStatusResponse(object $service, ReflectionClass $reflection, array $response, string $salesChannelId = 'sales-channel-1'): array
    {
        $method = $reflection->getMethod('parseOrderStatusResponse');
        $method->setAccessible(true);

        return $method->invoke($service, $response, $salesChannelId);
    }

    public function testValidResponseReturnsStatusesArray(): void
    {
        [$service, $logger, $reflection] = $this->buildService();

        $statuses = [
            ['external_contract_uuid' => 'abc-123', 'status' => 'active'],
        ];

        $response = [
            'code' => 200,
            'contents' => json_encode(['statuses' => $statuses]),
        ];

        $result = $this->invokeParseOrderStatusResponse($service, $reflection, $response);

        self::assertSame($statuses, $result);
        self::assertCount(0, $logger->errors);
    }

    public function testNon200ResponseReturnsEmptyArrayAndLogsError(): void
    {
        [$service, $logger, $reflection] = $this->buildService();

        $response = [
            'code' => 500,
            'contents' => 'Internal Server Error',
        ];

        $result = $this->invokeParseOrderStatusResponse($service, $reflection, $response);

        self::assertSame([], $result);
        self::assertCount(1, $logger->errors);
        self::assertStringContainsString('non-200 response', $logger->errors[0]['message']);
    }

    public function testInvalidJsonBodyReturnsEmptyArrayAndLogsError(): void
    {
        [$service, $logger, $reflection] = $this->buildService();

        $response = [
            'code' => 200,
            'contents' => 'not-json',
        ];

        $result = $this->invokeParseOrderStatusResponse($service, $reflection, $response);

        self::assertSame([], $result);
        self::assertCount(1, $logger->errors);
        self::assertStringContainsString('invalid/unexpected response body', $logger->errors[0]['message']);
    }

    public function testMissingStatusesKeyReturnsEmptyArrayAndLogsError(): void
    {
        [$service, $logger, $reflection] = $this->buildService();

        $response = [
            'code' => 200,
            'contents' => json_encode(['something_else' => true]),
        ];

        $result = $this->invokeParseOrderStatusResponse($service, $reflection, $response);

        self::assertSame([], $result);
        self::assertCount(1, $logger->errors);
    }

    public function testStatusesNotAnArrayReturnsEmptyArrayAndLogsError(): void
    {
        [$service, $logger, $reflection] = $this->buildService();

        $response = [
            'code' => 200,
            'contents' => json_encode(['statuses' => 'not-an-array']),
        ];

        $result = $this->invokeParseOrderStatusResponse($service, $reflection, $response);

        self::assertSame([], $result);
        self::assertCount(1, $logger->errors);
    }
}
