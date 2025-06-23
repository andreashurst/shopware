<?php
use Nwdxx\ItRechtConnector6\Controller\LegalController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    $routes->add('api.action.nwdxx.legal', '/api/v1/nwdxx/legal')
        ->controller([LegalController::class, 'handleLTIRequest'])
        ->methods(['POST'])
        ->defaults([
            '_routeScope' => ['api'],
            'auth_required' => false,
            'csrf_protected' => false,
        ])
    ;
};
