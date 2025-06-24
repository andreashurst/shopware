<?php declare(strict_types=1);

namespace AndreasHurst\MockupPlugin\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class MockupSubscriber implements EventSubscriberInterface
{
    private Environment $twig;
    
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }
    
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }
    
    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        
        // Always allow iframe embedding for this plugin
        if ($request->isMethod('GET') && str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        }
        
        // Only handle GET requests with mockup parameter
        if (!$request->isMethod('GET') || !$request->query->has('mockup')) {
            return;
        }
        
        // Only handle HTML responses
        if (!str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
            return;
        }
        
        $mockupType = $request->query->get('mockup');
        $url = $request->getUri();
        
        // Remove mockup parameter from URL for iframe
        $cleanUrl = $this->removeQueryParameter($url, 'mockup');
        
        // Clean mockup type parameter
        $mockupType = strtolower(str_replace(["-", "_"], "", $mockupType));
        
        // Determine template based on mockup type
        $template = $this->getTemplateForMockupType($mockupType);
        
        $templateData = [
            'mockup' => $mockupType,
            'url' => $cleanUrl,
            'originalContent' => $response->getContent()
        ];
        
        // Render mockup template
        $mockupContent = $this->twig->render($template, $templateData);
        $response->setContent($mockupContent);
    }
    
    private function getTemplateForMockupType(string $mockupType): string
    {
        $templates = [
            // Multi-device layouts
            'trible' => '@AndreasHurstMockup/storefront/page/mockup/trible.html.twig',
            'quad' => '@AndreasHurstMockup/storefront/page/mockup/quad.html.twig',
            'five' => '@AndreasHurstMockup/storefront/page/mockup/five.html.twig',
            'six' => '@AndreasHurstMockup/storefront/page/mockup/six.html.twig',
            'twelve' => '@AndreasHurstMockup/storefront/page/mockup/twelve.html.twig',
            'responsive' => '@AndreasHurstMockup/storefront/page/mockup/responsive.html.twig',
            
            // Single device layouts
            'desktop' => '@AndreasHurstMockup/storefront/page/device/desktop.html.twig',
            'laptop' => '@AndreasHurstMockup/storefront/page/device/laptop.html.twig',
            'tablet' => '@AndreasHurstMockup/storefront/page/device/tablet.html.twig',
            'tabletlandscape' => '@AndreasHurstMockup/storefront/page/device/tabletlandscape.html.twig',
            'mobile' => '@AndreasHurstMockup/storefront/page/device/mobile.html.twig',
            'iphone4' => '@AndreasHurstMockup/storefront/page/device/iphone4.html.twig',
            'iphone5' => '@AndreasHurstMockup/storefront/page/device/iphone5.html.twig',
            'iphone6' => '@AndreasHurstMockup/storefront/page/device/iphone6.html.twig',
            'iphone6landscape' => '@AndreasHurstMockup/storefront/page/device/iphone6landscape.html.twig',
            'iphone6plus' => '@AndreasHurstMockup/storefront/page/device/iphone6plus.html.twig',
            'iphone11pro' => '@AndreasHurstMockup/storefront/page/device/iphone11pro.html.twig',
            'iphone12' => '@AndreasHurstMockup/storefront/page/device/iphone12.html.twig',
            'iphone12max' => '@AndreasHurstMockup/storefront/page/device/iphone12max.html.twig',
            'ultrawide' => '@AndreasHurstMockup/storefront/page/device/ultrawide.html.twig'
        ];
        
        return $templates[$mockupType] ?? $templates['responsive'];
    }
    
    private function removeQueryParameter(string $url, string $parameter): string
    {
        $urlParts = parse_url($url);
        
        if (!isset($urlParts['query'])) {
            return $url;
        }
        
        parse_str($urlParts['query'], $queryParams);
        unset($queryParams[$parameter]);
        
        $urlParts['query'] = http_build_query($queryParams);
        
        // If no query params left, remove the query part
        if (empty($urlParts['query'])) {
            unset($urlParts['query']);
        }
        
        return $this->buildUrl($urlParts);
    }
    
    private function buildUrl(array $urlParts): string
    {
        $url = '';
        
        if (isset($urlParts['scheme'])) {
            $url .= $urlParts['scheme'] . '://';
        }
        
        if (isset($urlParts['host'])) {
            $url .= $urlParts['host'];
        }
        
        if (isset($urlParts['port'])) {
            $url .= ':' . $urlParts['port'];
        }
        
        if (isset($urlParts['path'])) {
            $url .= $urlParts['path'];
        }
        
        if (isset($urlParts['query'])) {
            $url .= '?' . $urlParts['query'];
        }
        
        if (isset($urlParts['fragment'])) {
            $url .= '#' . $urlParts['fragment'];
        }
        
        return $url;
    }
}