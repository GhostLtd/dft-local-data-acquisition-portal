<?php

namespace App;

use Ghost\GovUkCoreBundle\Features;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function getCacheDir(): string
    {
        return Features::isEnabled(Features::GAE_ENVIRONMENT)
            ? sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'symf-cache'
            : parent::getCacheDir();
    }

    public function getLogDir(): string
    {
        return Features::isEnabled(Features::GAE_ENVIRONMENT)
            ? sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'symf-log'
            : parent::getLogDir();
    }
}
