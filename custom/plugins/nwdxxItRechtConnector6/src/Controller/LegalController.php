<?php declare(strict_types=1);

namespace Nwdxx\ItRechtConnector6\Controller;

use Exception;
use ITRechtKanzlei\LTI;
use ITRechtKanzlei\LTIError;
use Nwdxx\ItRechtConnector6\Services\ConnectorService;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\PluginService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

require_once(__DIR__.'/../Resources/sdk/require_all.php');

class LegalController extends AbstractController
{
    private ConnectorService $connector;
    private string $shopVersion;
    private string $moduleVersion;

    public function __construct(ConnectorService $connector, PluginService $pluginService, string $shopVersion)
    {
        $this->connector = $connector;
        $this->shopVersion = $shopVersion;
        $plugin = $pluginService->getPluginByName(
            'NwdxxItRechtConnector6',
            new Context(new SystemSource())
        );
        $this->moduleVersion = $plugin->getVersion();
    }

    public function handleLTIRequest(Request $request): Response
    {
        $lti = new LTI(
            $this->connector,
            $this->shopVersion,
            $this->moduleVersion,
            true
        );
        $result = $lti->handleRequest($request->getContent(false));

        return new Response((string)$result, 200, [
            'Content-Type' => 'application/xml'
        ]);
    }
}
