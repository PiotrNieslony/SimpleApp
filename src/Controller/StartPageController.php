<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StartPageController extends AbstractController
{
    /**
     * @Route("/", name="start_page")
     */
    public function index(): Response
    {
        return $this->render('start_page/index.html.twig', [
            'controller_name' => 'StartPageController',
        ]);
    }
}
