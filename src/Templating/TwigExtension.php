<?php

namespace Go2FlowHeyLightPayment\Templating;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension
{
    /**
     * @var SystemConfigService
     */
    protected SystemConfigService $configService;

    public function __construct(SystemConfigService $configService) {
        $this->configService = $configService;
    }

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('heylight_best_term', [$this, 'bestTerm']),
            new TwigFunction('heylight_add_fee', [$this, 'addFee']),
        ];
    }

    public function bestTerm($price)
    {
        $terms = $this->configService->get('Go2FlowHeyLightPayment.settings.promotionTerms', null);
        $minInstalment = $this->configService->get('Go2FlowHeyLightPayment.settings.promotionWidgetMinInstalment', null);


        if(!is_array($terms)) {
            return false;
        }

        if(empty($minInstalment)) {
            $minInstalment = 0;
        }

        $minInstalment = $minInstalment * 100;

        rsort($terms);

        foreach ($terms as $term) {
            $instalmentPrice = $price / $term;
            if($instalmentPrice >= $minInstalment) {
                return $term;
            }
        }
        return false;
    }

    public function addFee($minorAmount)
    {
        $fee = $this->configService->get('Go2FlowHeyLightPayment.settings.promotionWidgetFee', null);
        $fee = (!empty($fee) ? (float) str_replace(',', '.', $fee) : 0);
        if ($fee > 0) {
            $minorAmount = round($minorAmount + ($minorAmount * ($fee / 100)));
        }
        return $minorAmount;
    }

    public function getAvailableTerms($terms, $minorAmount, $minimumInstalmentPrice)
    {
        $availableTerms = [];

        $terms = array_map('intval', $terms);
        rsort($terms, SORT_NUMERIC);
        foreach ($terms as $term) {
            $installPrice = $minorAmount / $term;
            if ($installPrice >= $minimumInstalmentPrice) {
                $availableTerms[] = $term;
            }
        }
        return $availableTerms;
    }
}
