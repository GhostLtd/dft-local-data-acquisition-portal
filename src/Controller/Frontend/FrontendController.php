<?php

namespace App\Controller\Frontend;

use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class FrontendController extends AbstractController
{
    #[Route(path: '/accessibility-statement', name: 'app_pages_accessibility')]
    #[Template('frontend/accessibility-statement.html.twig')]
    public function accessibilityStatement(): array
    {
        return [];
    }
}
