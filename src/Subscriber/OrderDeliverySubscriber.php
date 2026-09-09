<?php declare(strict_types=1);

namespace Go2FlowHeyLightPayment\Subscriber;

use Go2FlowHeyLightPayment\Handler\PaymentHandler;
use Go2FlowHeyLightPayment\Installer\Modules\PaymentMethodInstaller;
use Go2FlowHeyLightPayment\Service\HeyLightApiService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\StateMachine\Event\StateMachineStateChangeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderDeliverySubscriber implements EventSubscriberInterface
{

    private EntityRepository $orderDeliveryRepository;
    private HeyLightApiService $heyLightApiService;
    private LoggerInterface $logger;

    public function __construct(
        EntityRepository  $orderDeliveryRepository,
        HeyLightApiService $heyLightApiService,
        LoggerInterface $logger
    )
    {
        $this->orderDeliveryRepository = $orderDeliveryRepository;
        $this->heyLightApiService = $heyLightApiService;
        $this->logger = $logger;
    }

    /**
     * @return array<mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'state_machine.order_delivery.state_changed' => 'onOrderDeliveryChanged',
        ];
    }

    /**
     * @param StateMachineStateChangeEvent $event
     */
    public function onOrderDeliveryChanged(StateMachineStateChangeEvent $event): void
    {
        if (
            $event->getTransitionSide() !== StateMachineStateChangeEvent::STATE_MACHINE_TRANSITION_SIDE_ENTER
            || $event->getStateName() !== OrderDeliveryStates::STATE_SHIPPED
        ) {
            return;
        }
        $order = $this->getOrderByDelivery($event->getTransition()->getEntityId(), $event->getContext());
        if ($order) {
            $externalId = $this->getExternalIdFromOrder($order);
            if ($externalId) {
                $confirmed = $this->heyLightApiService->confirmDelivery($externalId, $order->getSalesChannelId());
                if (!$confirmed) {
                    // NOTE: no automatic retry is implemented here. Building a
                    // reliable retry (state tracking + a new scheduled task)
                    // is a bigger feature on its own and out of scope for
                    // this fix; recommended as a separate follow-up ticket.
                    // For now we at least make the failure visible so it can
                    // be confirmed manually with HeyLight/support.
                    $this->logger->error('HeyLight: confirmDelivery API call failed', [
                        'orderId'       => $order->getId(),
                        'orderNumber'   => $order->getOrderNumber(),
                        'externalId'    => $externalId,
                        'salesChannelId'=> $order->getSalesChannelId(),
                    ]);
                }
            }
        }
    }

    private function getOrderByDelivery(string $deliveryId, Context $context): ?OrderEntity
    {
        $criteria = new Criteria([$deliveryId]);
        $criteria->addAssociation('order.transactions.paymentMethod');
        $criteria->addFilter(
            new EqualsAnyFilter(
                'order.transactions.paymentMethod.technicalName',
                [
                    (PaymentHandler::PAYMENT_METHOD_PREFIX . PaymentMethodInstaller::HEYLIGHT_METHOD),
                    (PaymentHandler::PAYMENT_METHOD_PREFIX . PaymentMethodInstaller::HEYLIGHT_CREDIT_METHOD)
                ]
            )
        );
        $criteria->getAssociation('order.transactions')
            ->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        /** @var OrderDeliveryEntity $delivery */
        $delivery = $this->orderDeliveryRepository->search($criteria, $context)->first();
        return $delivery?->getOrder();
    }
    private function getExternalIdFromOrder(OrderEntity $order): ?string
    {
        $transaction = $order->getTransactions()->first();
        if (!$transaction || !array_key_exists('external_contract_uuid', $transaction->getCustomFields())) {
            return null;
        }
        return $transaction->getCustomFields()['external_contract_uuid'];
    }
}
