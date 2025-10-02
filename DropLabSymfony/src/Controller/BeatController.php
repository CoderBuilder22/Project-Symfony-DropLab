<?php

namespace App\Controller;

use App\Entity\Beat;
use App\Form\BeatType;
use App\Repository\BeatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/beats")
 */
class BeatController extends AbstractController
{
    /**
     * @Route("/", name="app_beat_index", methods={"GET"})
     */
    public function index(Request $request, BeatRepository $beatRepository): Response
    {
        $genre = $request->query->get('genre');
        $query = $request->query->get('q');
        $bpm = $request->query->get('bpm');
        $price = $request->query->get('price');

        // Convert bpm and price to appropriate types or null
        $bpm = $bpm !== null ? (int)$bpm : null;
        $price = $price !== null ? (float)$price : null;

        if ($query) {
            $beats = $beatRepository->search($query);
        } else if ($bpm !== null || $price !== null) {
            $beats = $beatRepository->findByBpmAndPrice($bpm, $price, $genre);
        } else if ($genre) {
            $beats = $beatRepository->findByGenre($genre);
        } else {
            $beats = $beatRepository->findAll();
        }

        return $this->render('beat/index.html.twig', [
            'beats' => $beats,
            'selected_genre' => $genre,
            'search_query' => $query,
            'selected_bpm' => $bpm,
            'selected_price' => $price,
        ]);
    }

    /**
     * @Route("/new", name="app_beat_new", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $beat = new Beat();
        $beat->setProducer($this->getUser());
        
        $form = $this->createForm(BeatType::class, $beat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($beat);
            $entityManager->flush();

            return $this->redirectToRoute('app_beat_index');
        }

        return $this->render('beat/new.html.twig', [
            'beat' => $beat,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_beat_show", methods={"GET"})
     */
    public function show(Beat $beat): Response
    {
        return $this->render('beat/show.html.twig', [
            'beat' => $beat,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_beat_edit", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function edit(Request $request, Beat $beat, EntityManagerInterface $entityManager): Response
    {
        // Check if the current user is the producer of the beat
        if ($beat->getProducer() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only edit your own beats.');
        }

        $form = $this->createForm(BeatType::class, $beat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_beat_index');
        }

        return $this->render('beat/edit.html.twig', [
            'beat' => $beat,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_beat_delete", methods={"POST"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function delete(Request $request, Beat $beat, EntityManagerInterface $entityManager): Response
    {
        if ($beat->getProducer() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only delete your own beats.');
        }

        if ($this->isCsrfTokenValid('delete'.$beat->getId(), $request->request->get('_token'))) {
            $entityManager->remove($beat);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_beat_index');
    }
} 