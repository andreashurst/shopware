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
            'trible' => '@AndreasHurstMockup/storefront/page/mockup/trible.html.twig',
            'quad' => '@AndreasHurstMockup/storefront/page/mockup/quad.html.twig',
            'five' => '@AndreasHurstMockup/storefront/page/mockup/five.html.twig',
            'six' => '@AndreasHurstMockup/storefront/page/mockup/six.html.twig',
            'twelve' => '@AndreasHurstMockup/storefront/page/mockup/twelve.html.twig',
            'responsive' => '@AndreasHurstMockup/storefront/page/mockup/responsive.html.twig'
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