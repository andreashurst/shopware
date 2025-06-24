<?php declare(strict_types=1);

namespace DeviceMockupViewer\Subscriber;

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

        // Allow iframe embedding for this plugin
        if ($request->isMethod('GET') && str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        // Only handle GET requests with mockup parameter
        if (!$request->isMethod('GET') || !$request->query->has('mockup')) {
            return;
        }

        $mockupType = $request->query->get('mockup');
        $template = $this->getTemplateForMockupType($mockupType);

        if (!$template) {
            return;
        }

        // Create clean URL without mockup parameter for iframe
        $cleanUrl = $this->getCleanUrl($request);

        try {
            $content = $this->twig->render($template, [
                'cleanUrl' => $cleanUrl,
                'mockupType' => $mockupType,
            ]);

            $response->setContent($content);
        } catch (\Exception $e) {
            // Silent fail - return original content
        }
    }

    private function getTemplateForMockupType(string $mockupType): ?string
    {
        $deviceTemplates = [
            // iPhones
            'iphone4' => '@DeviceMockupViewer/storefront/device/iphone4.html.twig',
            'iphone5' => '@DeviceMockupViewer/storefront/device/iphone5.html.twig',
            'iphone6' => '@DeviceMockupViewer/storefront/device/iphone6.html.twig',
            'iphone6plus' => '@DeviceMockupViewer/storefront/device/iphone6plus.html.twig',
            'iphone11pro' => '@DeviceMockupViewer/storefront/device/iphone11pro.html.twig',
            'iphone12' => '@DeviceMockupViewer/storefront/device/iphone12.html.twig',
            'iphone12max' => '@DeviceMockupViewer/storefront/device/iphone12max.html.twig',
            'iphone13' => '@DeviceMockupViewer/storefront/device/iphone13.html.twig',
            'iphone13mini' => '@DeviceMockupViewer/storefront/device/iphone13mini.html.twig',
            'iphone13pro' => '@DeviceMockupViewer/storefront/device/iphone13pro.html.twig',
            'iphone13promax' => '@DeviceMockupViewer/storefront/device/iphone13promax.html.twig',
            'iphone14' => '@DeviceMockupViewer/storefront/device/iphone14.html.twig',
            'iphone14plus' => '@DeviceMockupViewer/storefront/device/iphone14plus.html.twig',
            'iphone14pro' => '@DeviceMockupViewer/storefront/device/iphone14pro.html.twig',
            'iphone14promax' => '@DeviceMockupViewer/storefront/device/iphone14promax.html.twig',
            'iphone15' => '@DeviceMockupViewer/storefront/device/iphone15.html.twig',
            'iphone15plus' => '@DeviceMockupViewer/storefront/device/iphone15plus.html.twig',
            'iphone15pro' => '@DeviceMockupViewer/storefront/device/iphone15pro.html.twig',
            'iphone15promax' => '@DeviceMockupViewer/storefront/device/iphone15promax.html.twig',
            
            // Samsung Galaxy
            'galaxys21' => '@DeviceMockupViewer/storefront/device/galaxys21.html.twig',
            'galaxys22' => '@DeviceMockupViewer/storefront/device/galaxys22.html.twig',
            'galaxys23' => '@DeviceMockupViewer/storefront/device/galaxys23.html.twig',
            'galaxys24' => '@DeviceMockupViewer/storefront/device/galaxys24.html.twig',
            'galaxynote20' => '@DeviceMockupViewer/storefront/device/galaxynote20.html.twig',
            'galaxyfold' => '@DeviceMockupViewer/storefront/device/galaxyfold.html.twig',
            
            // Google Pixel
            'pixel6' => '@DeviceMockupViewer/storefront/device/pixel6.html.twig',
            'pixel7' => '@DeviceMockupViewer/storefront/device/pixel7.html.twig',
            'pixel8' => '@DeviceMockupViewer/storefront/device/pixel8.html.twig',
            'pixel8pro' => '@DeviceMockupViewer/storefront/device/pixel8pro.html.twig',
            
            // iPads
            'ipad' => '@DeviceMockupViewer/storefront/device/ipad.html.twig',
            'ipadmini' => '@DeviceMockupViewer/storefront/device/ipadmini.html.twig',
            'ipadair' => '@DeviceMockupViewer/storefront/device/ipadair.html.twig',
            'ipadpro11' => '@DeviceMockupViewer/storefront/device/ipadpro11.html.twig',
            'ipadpro13' => '@DeviceMockupViewer/storefront/device/ipadpro13.html.twig',
            
            // MacBooks
            'macbookair' => '@DeviceMockupViewer/storefront/device/macbookair.html.twig',
            'macbookpro14' => '@DeviceMockupViewer/storefront/device/macbookpro14.html.twig',
            'macbookpro16' => '@DeviceMockupViewer/storefront/device/macbookpro16.html.twig',
            
            // Surface
            'surfacepro' => '@DeviceMockupViewer/storefront/device/surfacepro.html.twig',
            'surfacelaptop' => '@DeviceMockupViewer/storefront/device/surfacelaptop.html.twig',
            'surfacestudio' => '@DeviceMockupViewer/storefront/device/surfacestudio.html.twig',
            
            // Desktop/Laptop
            'desktop' => '@DeviceMockupViewer/storefront/device/desktop.html.twig',
            'laptop' => '@DeviceMockupViewer/storefront/device/laptop.html.twig',
            'ultrawide' => '@DeviceMockupViewer/storefront/device/ultrawide.html.twig',
            'monitor4k' => '@DeviceMockupViewer/storefront/device/monitor4k.html.twig',
            
            // Gaming
            'steamdeck' => '@DeviceMockupViewer/storefront/device/steamdeck.html.twig',
            'nintendoswitch' => '@DeviceMockupViewer/storefront/device/nintendoswitch.html.twig',
            
            // Smart TV
            'smarttv55' => '@DeviceMockupViewer/storefront/device/smarttv55.html.twig',
            'smarttv65' => '@DeviceMockupViewer/storefront/device/smarttv65.html.twig',
            'appletvscreen' => '@DeviceMockupViewer/storefront/device/appletvscreen.html.twig',
            
            // Multi-device layouts
            'responsive' => '@DeviceMockupViewer/storefront/layout/responsive.html.twig',
            'trible' => '@DeviceMockupViewer/storefront/layout/trible.html.twig',
            'quad' => '@DeviceMockupViewer/storefront/layout/quad.html.twig',
            'five' => '@DeviceMockupViewer/storefront/layout/five.html.twig',
            'six' => '@DeviceMockupViewer/storefront/layout/six.html.twig',
            'twelve' => '@DeviceMockupViewer/storefront/layout/twelve.html.twig',
        ];

        return $deviceTemplates[$mockupType] ?? null;
    }

    private function getCleanUrl($request): string
    {
        $url = $request->getUri();
        $parsedUrl = parse_url($url);
        
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
            unset($queryParams['mockup']);
            
            $cleanQuery = http_build_query($queryParams);
            $cleanUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            
            if (isset($parsedUrl['port'])) {
                $cleanUrl .= ':' . $parsedUrl['port'];
            }
            
            $cleanUrl .= $parsedUrl['path'] ?? '/';
            
            if (!empty($cleanQuery)) {
                $cleanUrl .= '?' . $cleanQuery;
            }
            
            return $cleanUrl;
        }
        
        return rtrim($url, '?');
    }
}