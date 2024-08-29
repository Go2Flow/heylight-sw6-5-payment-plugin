<?php

declare(strict_types=1);

namespace Go2FlowHeyLightPayment\Controller;

use Go2FlowHeyLightPayment\Service\HeyLightApiService;
use Symfony\Component\Routing\Annotation\Route;

use Shopware\Core\Framework\Context;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route(defaults={"_routeScope"={"api"}})
 */
class SettingsController extends AbstractController
{
    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var HeyLightApiService|null
     */
    protected ?HeyLightApiService $heyLightApiService;

    /**
     * @param ContainerInterface $container
     * @param HeyLightApiService $heyLightApiService
     * @param LoggerInterface $logger
     */
    public function __construct(
        ContainerInterface $container,
        HeyLightApiService $heyLightApiService,
        LoggerInterface    $logger
    )
    {
        $this->setContainer($container);
        $this->heyLightApiService = $heyLightApiService;
        $this->logger = $logger;
    }

    /**
     * @Route("/api/_action/heylight_settings_service/validate-api-credentials", name="api.action.heylight_settings_service.validate.api.credentials", methods={"POST"})
     * @throws \Exception
     */
    public function validateApiCredentials(Request $request, Context $context): JsonResponse
    {
        $error = false;

        $merchant_key = $request->get('merchant_key', []);

        try {

            $token = $this->heyLightApiService->testAuthTransactionToken($merchant_key);

            if(!$token) {
                $error = true;
            }

        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        return new JsonResponse(['credentialsValid' => !$error, 'error' => $error]);

    }

}
