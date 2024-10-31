<?php

namespace App\EventSubscriber;

use App\Utility\MaintenanceModeHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Twig\Environment;

class MaintenanceLockSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected Environment               $twig,
        protected MaintenanceModeHelper     $maintenanceModeHelper,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $routeName = $event->getRequest()->attributes->get('_route');

        if (!$routeName) {
            // This may happen in case of an error, for example:
            // We'll end up with error_controller, and no route defined
            return;
        }

        $clientIp = $event->getRequest()->getClientIp();

        if (!$this->maintenanceModeHelper->isMaintenanceModeEnabledForRouteAndIp($routeName, $clientIp)) {
            return;
        }

        $event->setResponse(new Response(
            $this->twig->render('bundles/TwigBundle/Exception/maintenance.html.twig'),
            Response::HTTP_OK, // 200, not 503, so that AppEngine doesn't take instances out of service
            ['X-Robots-Tag' => 'noindex']
        ));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.request' => ['onKernelRequest', 20],
        ];
    }
}
