<?php declare(strict_types=1);

namespace Go2FlowHeyLightPayment\Service;

use Go2FlowHeyLightPayment\Handler\PaymentHandler;
use Go2FlowHeyLightPayment\Handler\TransactionHandler;
use Go2FlowHeyLightPayment\Helper\Transaction;
use Go2FlowHeyLightPayment\Installer\Modules\PaymentMethodInstaller;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class OrderService
{

    private EntityRepository $orderRepository;
    private HeyLightApiService $heyLightApiService;
    private TransactionHandler $transactionHandler;
    private EntityRepository $transactionRepository;
    private LoggerInterface $logger;

    /**
     * @param EntityRepository $orderRepository
     * @param EntityRepository $transactionRepository
     * @param HeyLightApiService $heyLightApiService
     * @param TransactionHandler $transactionHandler
     * @param LoggerInterface $logger
     */
    public function __construct(
        EntityRepository   $orderRepository,
        EntityRepository   $transactionRepository,
        HeyLightApiService $heyLightApiService,
        TransactionHandler $transactionHandler,
        LoggerInterface    $logger,
    )
    {
        $this->orderRepository = $orderRepository;
        $this->heyLightApiService = $heyLightApiService;
        $this->transactionHandler = $transactionHandler;
        $this->transactionRepository = $transactionRepository;
        $this->logger = $logger;
    }

    /**
     * @return RepositoryIterator
     */
    public function getOrdersIterator() :RepositoryIterator
    {
        $criteria = $this->getOrderCriteria();

        return new RepositoryIterator($this->orderRepository, Context::createDefaultContext(), $criteria);
    }

    /**
     * @return OrderCollection
     */
    public function getOrders() :OrderCollection
    {
        $criteria = $this->getOrderCriteria();

        return $this->orderRepository->search($criteria, Context::createDefaultContext())->getEntities();
    }

    /**
     * @return Criteria
     */
    private function getOrderCriteria(): Criteria
    {
        $criteria = new Criteria();
        $criteria->setLimit(10);
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->addSorting(new FieldSorting('id', FieldSorting::DESCENDING));
        // Include STATE_IN_PROGRESS in addition to STATE_OPEN: HeyLight's
        // "pending"/"awaiting_confirmation" statuses are mapped to
        // Transaction::WAITING, which moves the Shopware transaction to
        // STATE_IN_PROGRESS (see TransactionHandler::handleTransactionStatus).
        // Without this, the scheduled task would stop finding the order
        // after the first sync and never learn about the final outcome.
        $criteria->addFilter(
            new EqualsAnyFilter('order.transactions.stateMachineState.technicalName', [
                OrderTransactionStates::STATE_OPEN,
                OrderTransactionStates::STATE_IN_PROGRESS,
            ])
        );
        $criteria->addFilter(
            new EqualsAnyFilter(
                'order.transactions.paymentMethod.technicalName',
                [
                    (PaymentHandler::PAYMENT_METHOD_PREFIX . PaymentMethodInstaller::HEYLIGHT_METHOD),
                    (PaymentHandler::PAYMENT_METHOD_PREFIX . PaymentMethodInstaller::HEYLIGHT_CREDIT_METHOD)
                ]
            )
        );

        $criteria->addAssociations([
            'salesChannel', 'transactions'
        ]);
        $criteria->getAssociation('transactions')->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        return $criteria;
    }

    /**
     * @param OrderCollection $orders
     * @return void
     */
    public function workOrders(OrderCollection $orders): void
    {
        $channels = [];
        foreach ($orders as $order) {
            $channelId = $order->getSalesChannelId();
            if (!array_key_exists($channelId, $channels)) {
                $channels[$channelId] = [];
            }
            $transaction = $order->getTransactions()->first();
            if (
                $transaction
                && $transaction->getCustomFields()
                && array_key_exists('external_contract_uuid', $transaction->getCustomFields())
            ) {
                $channels[$channelId][] = $transaction->getCustomFields()['external_contract_uuid'];
            }
        }
        $newStatusResult = [];
        foreach ($channels as $channelId => $externalIds) {
            if (empty($externalIds)) {
                continue;
            }
            foreach ($this->heyLightApiService->getOrderStatus($externalIds, $channelId) as $statusItem) {
                if (empty($statusItem['external_uuid']) || !isset($statusItem['status'])) {
                    continue;
                }
                $newStatusResult[$statusItem['external_uuid']] = $statusItem['status'];
            }
        }
        $context = Context::createDefaultContext();
        foreach ($orders as $order) {
            $transaction = $order->getTransactions()->first();
            $externalId = $transaction?->getCustomFields()['external_contract_uuid'] ?? null;
            if ($externalId === null || !array_key_exists($externalId, $newStatusResult)) {
                continue;
            }
            $newStatus = $newStatusResult[$externalId];
            $mappedStatus = Transaction::mapStatus($newStatus);
            if ($mappedStatus === null) {
                // Unknown/unmapped HeyLight status: do NOT touch the
                // transaction (see Transaction::mapStatus doc block). Log it
                // and let the next scheduled run retry once HeyLight
                // reports a known status.
                $this->logger->warning('HeyLight: unknown order status received from API, skipping transaction update', [
                    'orderId'        => $order->getId(),
                    'transactionId'  => $transaction?->getId(),
                    'externalId'     => $externalId,
                    'unmappedStatus' => $newStatus,
                ]);
                continue;
            }
            $this->transactionHandler->handleTransactionStatus(
                $transaction,
                $mappedStatus,
                $context
            );
        }
    }

    /**
     * @param string $transactionId
     * @param Context $context
     * @return bool
     */
    public function fullRefund(string $transactionId, Context $context): bool
    {
        $criteria = new Criteria([$transactionId]);
        $criteria->addAssociations([
            'order', 'order.currency'
        ]);
        /** @var OrderTransactionEntity $transaction */
        $transaction = $this->transactionRepository->search($criteria, $context)->first();
        $success = $this->heyLightApiService->refund(
            $transaction->getCustomFields()['external_contract_uuid'],
            $transaction->getAmount()->getTotalPrice(),
            $transaction->getOrder()->getCurrency()->getIsoCode(),
            $transaction->getOrder()->getSalesChannelId()
        );
        if ($success) {
            $this->transactionHandler->handleTransactionStatus($transaction, Transaction::REFUNDED, $context);
        }
        return $success;
    }
}
