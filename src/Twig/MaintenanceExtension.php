<?php

namespace App\Twig;

use App\Utility\MaintenanceModeHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MaintenanceExtension extends AbstractExtension
{
    public function __construct(
        protected MaintenanceModeHelper $maintenanceModeHelper,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_maintenance_mode_enabled', $this->maintenanceModeHelper->isMaintenanceModeEnabled()),
        ];
    }
}
