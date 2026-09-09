<?php

declare(strict_types=1);

namespace Go2FlowHeyLightPayment\Handler;

use Go2FlowHeyLightPayment\Helper\Transaction;
use Go2FlowHeyLightPayment\Service\HeyLightApiService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;

class PaymentHandler extends AbstractPaymentHandler
{

    const PAYMENT_METHOD_PREFIX = 'heylight_';
    const BASE_URL = 'https://origination.heidipay.com';
    const SANDBOX_BASE_URL = 'https://sbx-origination.heidipay.io';

    /**
     * @var OrderTransactionStateHandler
     */
    protected OrderTransactionStateHandler $transactionStateHandler;

    /**
     * @var ContainerInterface
     */
    protected ContainerInterface $container;

    /**
     * @var HeyLightApiService
     */
    protected HeyLightApiService $heyLightApiService;

    /**
     * @var TransactionHandler
     */
    protected TransactionHandler $transactionHandler;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @param OrderTransactionStateHandler $transactionStateHandler
     * @param ContainerInterface $container
     * @param HeyLightApiService $heyLightApiService
     * @param TransactionHandler $transactionHandler
     * @param $logger
     */
    public function __construct(
        OrderTransactionStateHandler $transactionStateHandler,
        ContainerInterface           $container,
        HeyLightApiService           $heyLightApiService,
        TransactionHandler           $transactionHandler,
                                     $logger
    ) {
        $this->transactionStateHandler = $transactionStateHandler;
        $this->container = $container;
        $this->heyLightApiService = $heyLightApiService;
        $this->transactionHandler = $transactionHandler;
        $this->logger = $logger;
    }

    public function supports(PaymentHandlerType $type, string $paymentMethodId, Context $context): bool
    {
        return false;
    }

    /**
     * Redirects to the payment page
     *
     * @param Request $request
     * @param PaymentTransactionStruct $transaction
     * @param Context $context
     * @param Struct|null $validateStruct
     * @return RedirectResponse|null
     */
    public function pay(Request $request, PaymentTransactionStruct $transaction, Context $context, ?Struct $validateStruct): ?RedirectResponse
    {
        $transactionId = $transaction->getOrderTransactionId();
        $orderTransaction = $this->getOrderTransaction($transactionId, $context);
        $order = $orderTransaction->getOrder();
        $totalAmount = $orderTransaction->getAmount()->getTotalPrice();

        // Workaround if amount is 0
        if ($totalAmount <= 0) {
            $this->transactionStateHandler->paid($transactionId, $context);
            return null;
        }

        // Create HeyLight Link for checkout and redirect user
        try {
            $gateway = $this->heyLightApiService->processPayment(
                $order,
                $orderTransaction,
                (string) $transaction->getReturnUrl(),
                $context
            );

            if (
                !\is_array($gateway)
                || empty($gateway['external_contract_uuid'])
                || empty($gateway['redirect_url'])
            ) {
                $this->logger->error('HeyLight: invalid processPayment response', [
                    'orderId'       => $order->getId(),
                    'transactionId' => $transactionId,
                    'response'      => $gateway,
                ]);

                throw PaymentException::asyncProcessInterrupted(
                    $transactionId,
                    'HeyLight did not return a valid payment session.'
                );
            }

            $this->transactionHandler->saveTransactionCustomFields(
                $context,
                $transactionId,
                [ 'external_contract_uuid' => $gateway['external_contract_uuid'] ]
            );

            $redirectUrl = $gateway['redirect_url'] ;
        } catch (PaymentException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw PaymentException::asyncProcessInterrupted(
                $transactionId,
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        }

        return new RedirectResponse($redirectUrl);
    }

    /**
     * @param Request $request
     * @param PaymentTransactionStruct $transaction
     * @param Context $context
     */
    public function finalize(Request $request, PaymentTransactionStruct $transaction, Context $context): void
    {
        $transactionId = $transaction->getOrderTransactionId();
        $orderTransaction = $this->getOrderTransaction($transactionId, $context);
        $order = $orderTransaction->getOrder();

        $heyLightTransactionStatus = OrderTransactionStates::STATE_OPEN;

        $stateMachineState = $orderTransaction->getStateMachineState();
        if (!$stateMachineState) {
            $stateMachineState = $this->transactionHandler->getStateMachineState($orderTransaction->getStateId(), $context);
        }

        $customFields = $orderTransaction->getCustomFields();
        $externalContractUuid = $customFields['external_contract_uuid'] ?? null;
        $totalAmount = $orderTransaction->getAmount()->getTotalPrice();

        if ($totalAmount <= 0) {
            if (OrderTransactionStates::STATE_PAID === $stateMachineState->getTechnicalName()) return;
            $this->transactionStateHandler->paid($orderTransaction->getId(), $context);
            return;
        }

        if (empty($transactionId)) {
            throw PaymentException::customerCanceled(
                $transactionId,
                'Customer canceled the payment on the HeyLight page'
            );
        }

        $orderStatus = $externalContractUuid
            ? $this->heyLightApiService->checkOrderStatus($externalContractUuid, $order->getSalesChannelId())
            : false;

        if ( ( !$externalContractUuid || !$orderStatus ) && $totalAmount > 0) {
            throw PaymentException::customerCanceled(
                $transactionId,
                'Customer canceled the payment on the HeyLight page'
            );
        }

        if ($totalAmount <= 0 || $orderStatus == true) {
            $heyLightTransactionStatus = Transaction::CONFIRMED;
        }

        $this->transactionHandler->handleTransactionStatus($orderTransaction, $heyLightTransactionStatus, $context);

        if (!in_array($heyLightTransactionStatus, [Transaction::CANCELLED, Transaction::DECLINED, Transaction::EXPIRED, Transaction::ERROR])){
            return;
        }
        throw PaymentException::customerCanceled(
            $transactionId,
            'Customer canceled the payment on the HeyLight page'
        );
    }

    /**
     * Loads the order transaction including all order associations needed
     * for building the HeyLight payment request.
     */
    private function getOrderTransaction(string $transactionId, Context $context): OrderTransactionEntity
    {
        $transactionRepo = $this->container->get('order_transaction.repository');

        $criteria = new Criteria([$transactionId]);
        $criteria->addAssociation('stateMachineState');
        $criteria->addAssociation('paymentMethod');
        $criteria->addAssociation('order.lineItems');
        $criteria->addAssociation('order.currency');
        $criteria->addAssociation('order.orderCustomer');
        $criteria->addAssociation('order.billingAddress.country');
        $criteria->addAssociation('order.salesChannel.domains');
        $criteria->addAssociation('order.transactions.paymentMethod');

        /** @var OrderTransactionEntity|null $orderTransaction */
        $orderTransaction = $transactionRepo->search($criteria, $context)->first();

        if ($orderTransaction === null || $orderTransaction->getOrder() === null) {
            throw PaymentException::invalidTransaction($transactionId);
        }

        return $orderTransaction;
    }
}
