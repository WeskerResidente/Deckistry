<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LoginController extends AbstractController
{
    #[Route('/login/custom', name: 'app_custom_login')]
    public function customLogin(): Response
    {
        return $this->render('security/login_custom.html.twig');
    }
}
