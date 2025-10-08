<?php

namespace App\Controller;

use App\Entity\Lieu;
use App\Form\LieuType;
use App\Repository\LieuRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// #[IsGranted('ROLE_ORGANISATEUR')]
#[Route('/lieux')]
final class LieuController extends AbstractController
{
    #[Route('', name: 'app_lieu_index', methods: ['GET'])]
    public function index(LieuRepository $lieuRepository): Response
    {
        return $this->render('lieu/index.html.twig', [
            'lieux' => $lieuRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_lieu_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $lieu = new Lieu();
        $form = $this->createForm(LieuType::class, $lieu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($lieu);
            $em->flush();
            $this->addFlash('success', 'Le lieu a été créé.');
            return $this->redirectToRoute('app_lieu_index');
        }

        return $this->render('lieu/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_lieu_show', methods: ['GET'])]
    public function show(Lieu $lieu): Response
    {
        return $this->render('lieu/show.html.twig', [
            'lieu' => $lieu,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_lieu_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Lieu $lieu, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(LieuType::class, $lieu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Le lieu a été mis à jour.');
            return $this->redirectToRoute('app_lieu_index');
        }

        return $this->render('lieu/edit.html.twig', [
            'form' => $form->createView(),
            'lieu' => $lieu,
        ]);
    }

    #[Route('/{id}', name: 'app_lieu_delete', methods: ['POST'])]
    public function delete(Request $request, Lieu $lieu, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$lieu->getId(), (string) $request->request->get('_token'))) {
            $em->remove($lieu);
            $em->flush();
            $this->addFlash('success', 'Le lieu a été supprimé.');
        }

        return $this->redirectToRoute('app_lieu_index');
    }
}


