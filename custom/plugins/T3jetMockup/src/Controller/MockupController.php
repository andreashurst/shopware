<?php declare(strict_types=1);

namespace T3jet\MockupPlugin\Controller;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MockupController extends StorefrontController
{
    #[Route('/mockup', name: 'frontend.mockup.page', defaults: ['_routeScope' => ['storefront']], methods: ['GET'])]
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
            'trible' => '@T3jetMockup/storefront/page/mockup/trible.html.twig',
            'quad' => '@T3jetMockup/storefront/page/mockup/quad.html.twig',
            'five' => '@T3jetMockup/storefront/page/mockup/five.html.twig',
            'six' => '@T3jetMockup/storefront/page/mockup/six.html.twig',
            'twelve' => '@T3jetMockup/storefront/page/mockup/twelve.html.twig',
            'responsive' => '@T3jetMockup/storefront/page/mockup/responsive.html.twig'
        ];
        
        return $templates[$mockupType] ?? $templates['responsive'];
    }
}