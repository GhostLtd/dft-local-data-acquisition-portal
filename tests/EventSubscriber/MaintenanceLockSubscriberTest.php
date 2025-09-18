<?php

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\MaintenanceLockSubscriber;
use App\Utility\MaintenanceModeHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Twig\Environment;

class MaintenanceLockSubscriberTest extends TestCase
{
    /** @var Environment&MockObject */
    private Environment $twig;
    /** @var MaintenanceModeHelper&MockObject */
    private MaintenanceModeHelper $maintenanceModeHelper;
    private MaintenanceLockSubscriber $subscriber;
    /** @var HttpKernelInterface&MockObject */
    private HttpKernelInterface $kernel;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);
        $this->maintenanceModeHelper = $this->createMock(MaintenanceModeHelper::class);
        $this->subscriber = new MaintenanceLockSubscriber($this->twig, $this->maintenanceModeHelper);
        $this->kernel = $this->createMock(HttpKernelInterface::class);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = MaintenanceLockSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey('kernel.request', $events);
        $this->assertEquals(['onKernelRequest', 20], $events['kernel.request']);
    }

    public function testDoesNothingWhenNoRoute(): void
    {
        $request = new Request();
        // No route set in attributes

        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->maintenanceModeHelper
            ->expects($this->never())
            ->method('isMaintenanceModeEnabledForRouteAndIp');

        $this->subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testDoesNothingWhenMaintenanceModeNotEnabled(): void
    {
        $request = Request::create('/');
        $request->attributes->set('_route', 'app_dashboard');

        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->maintenanceModeHelper
            ->expects($this->once())
            ->method('isMaintenanceModeEnabledForRouteAndIp')
            ->with('app_dashboard', '127.0.0.1')
            ->willReturn(false);

        $this->subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testSetsMaintenanceResponseWhenMaintenanceModeEnabled(): void
    {
        $request = Request::create('/');
        $request->attributes->set('_route', 'app_dashboard');

        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->maintenanceModeHelper
            ->expects($this->once())
            ->method('isMaintenanceModeEnabledForRouteAndIp')
            ->with('app_dashboard', '127.0.0.1')
            ->willReturn(true);

        $this->twig
            ->expects($this->once())
            ->method('render')
            ->with('bundles/TwigBundle/Exception/maintenance.html.twig')
            ->willReturn('<html>Maintenance</html>');

        $this->subscriber->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('<html>Maintenance</html>', $response->getContent());
        $this->assertEquals('noindex', $response->headers->get('X-Robots-Tag'));
    }

    public function testUsesCorrectClientIp(): void
    {
        $request = Request::create('/');
        $request->attributes->set('_route', 'app_dashboard');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->maintenanceModeHelper
            ->expects($this->once())
            ->method('isMaintenanceModeEnabledForRouteAndIp')
            ->with('app_dashboard', '192.168.1.1')
            ->willReturn(false);

        $this->subscriber->onKernelRequest($event);
    }

    public function testHandlesDifferentRoutes(): void
    {
        $routes = [
            'app_dashboard' => '10.0.0.1',
            'admin_users' => '10.0.0.2',
            'api_endpoint' => '10.0.0.3',
        ];

        $this->maintenanceModeHelper
            ->expects($this->exactly(3))
            ->method('isMaintenanceModeEnabledForRouteAndIp')
            ->willReturnCallback(function($route, $ip) use ($routes) {
                $this->assertArrayHasKey($route, $routes);
                $this->assertEquals($routes[$route], $ip);
                return false;
            });

        foreach ($routes as $route => $ip) {
            $request = Request::create('/');
            $request->attributes->set('_route', $route);
            $request->server->set('REMOTE_ADDR', $ip);

            $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);
            $this->subscriber->onKernelRequest($event);
        }
    }

    public function testResponseUses200StatusCodeForAppEngine(): void
    {
        $request = Request::create('/');
        $request->attributes->set('_route', 'app_dashboard');

        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->maintenanceModeHelper
            ->method('isMaintenanceModeEnabledForRouteAndIp')
            ->willReturn(true);

        $this->twig
            ->method('render')
            ->willReturn('Maintenance');

        $this->subscriber->onKernelRequest($event);

        $response = $event->getResponse();
        // Should be 200 OK, not 503 Service Unavailable, to prevent AppEngine from taking instances out of service
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testSetsRobotsHeaderToPreventIndexing(): void
    {
        $request = Request::create('/');
        $request->attributes->set('_route', 'app_dashboard');

        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->maintenanceModeHelper
            ->method('isMaintenanceModeEnabledForRouteAndIp')
            ->willReturn(true);

        $this->twig
            ->method('render')
            ->willReturn('Maintenance');

        $this->subscriber->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertEquals('noindex', $response->headers->get('X-Robots-Tag'));
    }

    public function testProcessesBothMainAndSubRequests(): void
    {
        $request = Request::create('/');
        $request->attributes->set('_route', 'app_dashboard');

        $this->maintenanceModeHelper
            ->expects($this->exactly(2))
            ->method('isMaintenanceModeEnabledForRouteAndIp')
            ->willReturn(false);

        // Test with sub-request
        $subRequestEvent = new RequestEvent($this->kernel, $request, HttpKernelInterface::SUB_REQUEST);
        $this->subscriber->onKernelRequest($subRequestEvent);

        // Test with main request
        $mainRequestEvent = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $this->subscriber->onKernelRequest($mainRequestEvent);
    }
}