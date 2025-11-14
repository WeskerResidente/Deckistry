<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CollectionController extends AbstractController
{
    #[Route('/collection', name: 'app_collection')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        return $this->render('collection/index.html.twig');
    }
}
