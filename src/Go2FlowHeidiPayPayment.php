<?php declare(strict_types=1);

namespace Go2FlowHeidiPayPayment;

use Doctrine\DBAL\Connection;
use Go2FlowHeidiPayPayment\Core\Content\WebhookTokens\WebhookTokenDefinition;
use Go2FlowHeidiPayPayment\Installer\HeidiPayPaymentInstaller;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Go2FlowHeidiPayPayment extends Plugin
{

    const HEIDIPAY_CURRENT_VERSION = '1.6.0';
    const CREDIT_CHECKOUT_IMAGE = 'https://storage.googleapis.com/heidi-public-images/heidipay_upstream_inline_logos/heidipay_cards_qr.svg';

    /**
     * @param ContainerBuilder $container
     * @return void
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * @param InstallContext $context
     */
    public function install(InstallContext $context): void
    {
        //get the config service
        $config = $this->container->get('Shopware\Core\System\SystemConfig\SystemConfigService');

        //set the specified values as defaults
        // This is the standard Sandbox API Key from Heidipay which is public, so no need to worry
        $config->set('Go2FlowHeidiPayPayment.settings.heidiSecretKey', "5f6b49230c80f8b894e27b528a063acdac887ceb");
        $config->set('Go2FlowHeidiPayPayment.settings.heidiMode', "0");
        $config->set('Go2FlowHeidiPayPayment.settings.heidiPromotionShowOnProduct', "1");
        $config->set('Go2FlowHeidiPayPayment.settings.heidiPromotionShowOnCart', "1");
        $config->set('Go2FlowHeidiPayPayment.settings.heidiPromotionWidgetFee', 0);
        $config->set('Go2FlowHeidiPayPayment.settings.heidiPromotionProductMode', "0");
        $config->set('Go2FlowHeidiPayPayment.settings.heidiPromotionWidgetMinAmount', 0);
        $config->set('Go2FlowHeidiPayPayment.settings.heidiPromotionWidgetMinInstalment', 1);
        $config->set('Go2FlowHeidiPayPayment.settings.heidiPromotionTerms', ["3", "6"]);
        $config->set('Go2FlowHeidiPayPayment.settings.heidiPromotionTermsCredit', ["12"]);
        $config->set('Go2FlowHeidiPayPayment.settings.heidiPromotionPublicApiKey', "-");
        $config->set('Go2FlowHeidiPayPayment.settings.heidiMaximumOrderValue', 1000);
        $this->getInstaller()->install($context);
        parent::install($context);
    }

    /**
     * @param UninstallContext $context
     */
    public function uninstall(UninstallContext $context): void
    {
        $this->getInstaller()->uninstall($context);
        if (!$context->keepUserData()) {
            $this->dropTables();
        }
        parent::uninstall($context);
    }

    private function dropTables(): void
    {
        $connection = $this->container->get(Connection::class);
        $connection->executeStatement(\sprintf('DROP TABLE IF EXISTS `%s`', WebhookTokenDefinition::ENTITY_NAME));
    }

    /**
     * @param ActivateContext $context
     */
    public function activate(ActivateContext $context): void
    {
        $this->getInstaller()->activate($context);
        parent::activate($context);
    }

    /**
     * @param DeactivateContext $context
     */
    public function deactivate(DeactivateContext $context): void
    {
        $this->getInstaller()->deactivate($context);
        parent::deactivate($context);
    }

    /**
     * @param UpdateContext $context
     */
    public function update(UpdateContext $context): void
    {
        /** @var SystemConfigService $config */
        $config = $this->container->get(SystemConfigService::class);
        if (\version_compare($context->getCurrentPluginVersion(), '1.2.0', '<')) {
            $config->set('Go2FlowHeidiPayPayment.settings.heidiPromotionProductMode', "0");
        }
        if (\version_compare($context->getCurrentPluginVersion(), '1.3.0', '<')) {
            $config->set('Go2FlowHeidiPayPayment.settings.heidiPromotionTermsCredit', ["12"]);
        }
        $this->getInstaller()->update($context);
        parent::update($context);
    }

    private function getInstaller(): HeidiPayPaymentInstaller
    {
        return new HeidiPayPaymentInstaller(
            $this->container->get('media.repository'),
            $this->container->get('media_folder.repository'),
            $this->container->get('payment_method.repository'),
            $this->container->get('Shopware\Core\Framework\Plugin\Util\PluginIdProvider'),
            $this->container->get(FileSaver::class),
        );
    }
}
