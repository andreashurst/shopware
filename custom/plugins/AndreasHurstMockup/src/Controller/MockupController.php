<?php declare(strict_types=1);

namespace AndreasHurst\MockupPlugin\Controller;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\Routing\Annotation\Route;

class MockupController extends StorefrontController
{
    /**
     * @Route("/mockup", name="frontend.mockup.page", methods={"GET"})
     * @RouteScope(scopes={"storefront"})
     */
    public function mockupPage(Request $request, SalesChannelContext $context): Response
    {
        $mockupType = $request->query->get('mockup', 'responsive');
        $url = $request->query->get('url', $request->getSchemeAndHttpHost());

        // Clean mockup type parameter
        $mockupType = strtolower(str_replace(["-", "_"], "", $mockupType));

        $templateData = [
            'mockup' => $mockupType,
            'url' => $url
        ];

        // Determine template based on mockup type
        $template = $this->getTemplateForMockupType($mockupType);

        return $this->renderStorefront($template, $templateData);
    }

    private function getTemplateForMockupType(string $mockupType): string
    {
        $templates = [
            'trible' => '@AndreasHurstMockup/storefront/page/mockup/trible.html.twig',
            'quad' => '@AndreasHurstMockup/storefront/page/mockup/quad.html.twig',
            'five' => '@AndreasHurstMockup/storefront/page/mockup/five.html.twig',
            'six' => '@AndreasHurstMockup/storefront/page/mockup/six.html.twig',
            'twelve' => '@AndreasHurstMockup/storefront/page/mockup/twelve.html.twig',
            'responsive' => '@AndreasHurstMockup/storefront/page/mockup/responsive.html.twig'
        ];

        return $templates[$mockupType] ?? $templates['responsive'];
    }
}
