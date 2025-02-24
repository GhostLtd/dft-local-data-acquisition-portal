<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void
    {
        $configDir = $this->getConfigDir();

        $container->import($configDir.'/{packages}/*.{php,yaml}');
        $container->import($configDir.'/{packages}/'.$this->environment.'/*.{php,yaml}');

        if (Features::isEnabled(Features::GAE_ENVIRONMENT)) {
            $container->import('../config/{packages}/gae/*.yaml');
        }

        if (is_file($configDir.'/services.yaml')) {
            $container->import($configDir.'/services.yaml');
            $container->import($configDir.'/{services}_'.$this->environment.'.yaml');
        } else {
            $container->import($configDir.'/{services}.php');
            $container->import($configDir.'/{services}_'.$this->environment.'.php');
        }
    }


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
