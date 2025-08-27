<?php

namespace App\Controller\Cron;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

class CreateReturnsCronController extends AbstractCronController
{
    /**
     * @throws Exception
     */
    #[Route(path: '/create-returns', name: 'create_returns')]
    public function messengerConsumer(KernelInterface $kernel): Response
    {
        return $this->runCommand(
            $kernel,
            'ldap:cron:create-returns',
            []
        );
    }
}
