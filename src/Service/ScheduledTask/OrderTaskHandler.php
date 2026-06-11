<?php declare(strict_types=1);

namespace Go2FlowHeyLightPayment\Service\ScheduledTask;

use Go2FlowHeyLightPayment\Service\OrderService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: OrderTask::class)]
class OrderTaskHandler extends ScheduledTaskHandler
{

    private OrderService $orderService;

    /**
     * @param EntityRepository $scheduledTaskRepository
     * @param LoggerInterface $logger
     * @param OrderService $orderService
     */
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        LoggerInterface $logger,
        OrderService $orderService
    )
    {
        parent::__construct($scheduledTaskRepository, $logger);
        $this->orderService = $orderService;
    }

    public function run(): void
    {
        $iterator = $this->orderService->getOrdersIterator();
        while (($result = $iterator->fetch()) !== null) {
            $this->exceptionLogger->info('HeyLight: working on '.$result->getTotal().' Orders');
            $this->orderService->workOrders($result->getEntities());
        }
    }
}
