<?php declare(strict_types=1);

namespace Go2FlowHeyLightPayment\Service\ScheduledTask;

use Go2FlowHeyLightPayment\Handler\TransactionHandler;
use Go2FlowHeyLightPayment\Service\OrderService;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(handles: OrderTask::class)]
class OrderTaskHandler extends ScheduledTaskHandler
{

    /**
     * @var Logger
     */
    private Logger $logger;
    private OrderService $orderService;

    /**
     * @param EntityRepository $scheduledTaskRepository
     * @param OrderService $orderService
     * @param string|null $name
     */
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        OrderService $orderService,
        string $name = null
    )
    {
        $this->scheduledTaskRepository = $scheduledTaskRepository;
        $this->orderService = $orderService;

        $logger = new Logger('heylight-status-cronjob');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/heylight-status-cronjob.log'));
        $this->logger = $logger;
    }

    public static function getHandledMessages(): iterable
    {
        return [ OrderTask::class ];
    }

    public function run(): void
    {
        $iterator = $this->orderService->getOrdersIterator();
        while (($result = $iterator->fetch()) !== null) {
            $this->logger->info('HeyLight: working on '.$result->getTotal().' Orders');
            $this->orderService->workOrders($result->getEntities());
        }
    }
}
