<?php declare(strict_types=1);

namespace Go2FlowHeyLightPayment\Service\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class OrderTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'heylight.order_task';
    }

    public static function getDefaultInterval(): int
    {
        return (60*15); // 15 minutes in seconds
    }
}
