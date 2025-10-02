<?php

namespace App\Controller;

use App\Repository\BeatRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="app_home")
     */
    public function index(BeatRepository $beatRepository): Response
    {
        // Get trending beats (latest 6 beats)
        $trending_beats = $beatRepository->findBy([], ['createdAt' => 'DESC'], 6);

        return $this->render('home/index.html.twig', [
            'trending_beats' => $trending_beats,
        ]);
    }
}
