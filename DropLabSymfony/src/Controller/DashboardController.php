<?php

namespace App\Controller;

use App\Entity\Beat;
use App\Form\BeatType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 * @Route("/dashboard")
 */
class DashboardController extends AbstractController
{
    /**
     * @Route("", name="app_dashboard")
     */
    public function index(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PRODUCER');

        $beats = $entityManager->getRepository(Beat::class)
            ->findBy(['producer' => $this->getUser()], ['createdAt' => 'DESC']);

        return $this->render('dashboard/index.html.twig', [
            'beats' => $beats,
        ]);
    }

    /**
     * @Route("/beat/new", name="app_dashboard_beat_new")
     */
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PRODUCER');

        $beat = new Beat();
        $form = $this->createForm(BeatType::class, $beat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $beat->setProducer($this->getUser());
            
            $audioFile = $form->get('audioFile')->getData();
            if ($audioFile) {
                $originalFilename = pathinfo($audioFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$audioFile->guessExtension();

                try {
                    $audioFile->move(
                        $this->getParameter('beats_directory'),
                        $newFilename
                    );
                    $beat->setAudioFile($newFilename);
                } catch (FileException $e) {
                    // Handle exception
                }
            }

            $coverImage = $form->get('coverImage')->getData();
            if ($coverImage) {
                $originalFilename = pathinfo($coverImage->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$coverImage->guessExtension();

                try {
                    $coverImage->move(
                        $this->getParameter('covers_directory'),
                        $newFilename
                    );
                    $beat->setCoverImage($newFilename);
                } catch (FileException $e) {
                    // Handle exception
                }
            }

            $entityManager->persist($beat);
            $entityManager->flush();

            $this->addFlash('success', 'Your beat has been uploaded successfully!');
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('dashboard/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/beat/{id}/edit", name="app_dashboard_beat_edit")
     */
    public function edit(Request $request, Beat $beat, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PRODUCER');

        // Ensure the user owns this beat
        if ($beat->getProducer() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot edit this beat.');
        }

        $form = $this->createForm(BeatType::class, $beat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $audioFile = $form->get('audioFile')->getData();
            if ($audioFile) {
                $originalFilename = pathinfo($audioFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$audioFile->guessExtension();

                try {
                    $audioFile->move(
                        $this->getParameter('beats_directory'),
                        $newFilename
                    );
                    $beat->setAudioFile($newFilename);
                } catch (FileException $e) {
                    // Handle exception
                }
            }

            $coverImage = $form->get('coverImage')->getData();
            if ($coverImage) {
                $originalFilename = pathinfo($coverImage->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$coverImage->guessExtension();

                try {
                    $coverImage->move(
                        $this->getParameter('covers_directory'),
                        $newFilename
                    );
                    $beat->setCoverImage($newFilename);
                } catch (FileException $e) {
                    // Handle exception
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Beat updated successfully!');
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('dashboard/edit.html.twig', [
            'form' => $form->createView(),
            'beat' => $beat,
        ]);
    }

    /**
     * @Route("/beat/{id}/delete", name="app_dashboard_beat_delete", methods={"POST"})
     */
    public function delete(Request $request, Beat $beat, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PRODUCER');

        if ($beat->getProducer() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot delete this beat.');
        }

        if ($this->isCsrfTokenValid('delete'.$beat->getId(), $request->request->get('_token'))) {
            $entityManager->remove($beat);
            $entityManager->flush();

            $this->addFlash('success', 'Beat deleted successfully!');
        }

        return $this->redirectToRoute('app_dashboard');
    }
} 