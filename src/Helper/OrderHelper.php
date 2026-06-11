<?php

namespace Go2FlowHeyLightPayment\Helper;

use Go2FlowHeyLightPayment\Service\WebhookService;
use Go2FlowHeyLightPayment\Templating\TwigExtension;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Newsletter\Exception\SalesChannelDomainNotFoundException;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class OrderHelper {

    /**
     * @param OrderEntity $order
     * @param string $url
     * @param array $terms
     * @param string $productType
     * @param string $webhookToken
     * @param SystemConfigService $configService
     * @return array
     */
	public static function prepareOrderData(
        OrderEntity $order,
        string $url,
        array $terms,
        string $productType,
        string $webhookToken,
        SystemConfigService $configService
    ): array
    {

        $templateFunctions = new TwigExtension($configService);

		$requestData = array(
			'amount_format'       => 'DECIMAL',
			'amount'              => [
				'amount'   => self::getFormattedPrice( $templateFunctions->addFee( $order->getAmountTotal() ) ),
				'currency' => $order->getCurrency()->getIsoCode(),
            ],
			'redirect_urls'       => [
				'success_url'     => $url.'&status=heylight_success',
				'failure_url'     => $url.'&status=heylight_fail',
            ],
            'webhooks'            => [
                'mapping_scheme'  => 'SHOPWARE',
                'status_url'      => self::getStorefrontUrl($order->getSalesChannel()).'/heylight/webhook/'.$order->getId().'/status',
                'token'           => $webhookToken,
            ],
			'origination_channel' => 'ecommerce',
			'financial_product_type' => $productType,
			'order_reference'     => $order->getOrderNumber(),
			'customer_details'    => [
				'email_address'   => $order->getOrderCustomer()->getEmail(),
				'first_name'      => $order->getBillingAddress()->getFirstName(),
				'last_name'       => $order->getBillingAddress()->getLastName(),
            ],
			'billing_address'     => [
				'zip_code'        => $order->getBillingAddress()->getZipcode(),
				'city'            => $order->getBillingAddress()->getCity(),
				'address_line_1'  => $order->getBillingAddress()->getStreet(),
				'country_code'    => $order->getBillingAddress()->getCountry()->getIso3(),
            ],
			'products'            => self::getProducts( $order, $terms, $configService ),
		);

		if ( $order->getBillingAddress()->getAdditionalAddressLine2() ) {
			$requestData['billing_address']['address_line_2'] = $order->getBillingAddress()->getAdditionalAddressLine2();
		}

		return $requestData;
	}

    /**
     * @param OrderEntity $order
     * @param array $terms
     * @param SystemConfigService $configService
     * @return array
     */
	public static function getProducts( OrderEntity $order, array $terms, SystemConfigService $configService ): array
    {
        $templateFunctions = new TwigExtension($configService);
		$items = [];
		foreach ( $order->getLineItems()->getElements() as $item ) {
            $widget_min_instalment = $configService->get('Go2FlowHeyLightPayment.settings.promotionWidgetMinInstalment', $order->getSalesChannelId());
            $minimumInstalmentPrice = (! empty($widget_min_instalment) ? (float) $widget_min_instalment : 1);
            $availableTerms = $templateFunctions->getAvailableTerms($terms, $templateFunctions->addFee( $order->getAmountTotal() ), $minimumInstalmentPrice);

			$tmp = [
				'sku'           => $item->getPayload()['productNumber'] ?? '',
				'quantity'      => $item->getQuantity(),
				'name'          => $item->getLabel(),
				'price'         => self::getFormattedPrice( $item->getTotalPrice() ),
                'allowed_terms' => $availableTerms
			];

            $items[] = $tmp;
		}

		return $items;
	}

	/**
	 * Convert price to a valid format 123456.78
	 *
	 * @param float|int|string $price
	 *
	 * @return string
	 */
	public static function getFormattedPrice(float|int|string $price ): string
    {
		return number_format( $price, 2, '.', '' );
	}

    /**
     * @param SalesChannelEntity $salesChannel
     * @return string
     */
    public static function getStorefrontUrl(SalesChannelEntity $salesChannel): string
    {
        $domains = $salesChannel->getDomains();
        if ($domains === null) {
            throw new SalesChannelDomainNotFoundException($salesChannel);
        }

        $domain = $domains->first();
        if ($domain === null) {
            throw new SalesChannelDomainNotFoundException($salesChannel);
        }

        return $domain->getUrl();
    }
}
