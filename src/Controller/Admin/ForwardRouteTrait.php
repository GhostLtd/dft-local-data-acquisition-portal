<?php

namespace App\Controller\Admin;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

trait ForwardRouteTrait
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * We need to redefine this method, passing false to the $catch argument of the handle method
     * This means that any exceptions bubble to the parent request, allowing us to handle it better there
     */
    protected function forward(string $controller, array $path = [], array $query = []): Response
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $path['_controller'] = $controller;
        $subRequest = $request->duplicate($query, null, $path);

        return $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);
    }
}