<?php declare(strict_types=1);

namespace Go2FlowHeyLightPayment\Exception;

use Shopware\Core\Framework\ShopwareHttpException;

class ConfigurationException extends ShopwareHttpException
{
    public function __construct(string $message, string $eventClass)
    {
        parent::__construct(
            'Failed processing the configuration: {{ errorMessage }}. {{ eventClass }}',
            [
                'errorMessage' => $message,
                'eventClass' => $eventClass,
            ]
        );
    }

    public function getErrorCode(): string
    {
        return 'HEYLIGHT_POST_CONFIGURATION';
    }

    public function getStatusCode(): int
    {
        return 422;
    }
}
