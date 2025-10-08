<?php

namespace App\Controller;

use App\Entity\Ville;
use App\Form\VilleType;
use App\Repository\VilleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

 #[IsGranted('ROLE_ORGANISATEUR')]
#[Route('/villes', name: 'app_ville_')]
final class VilleController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(VilleRepository $villeRepository): Response
    {
        return $this->render('ville/index.html.twig', [
            'villes' => $villeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $ville = new Ville();
        $form = $this->createForm(VilleType::class, $ville);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($ville);
            $em->flush();
            $this->addFlash('success', 'La ville a été créée.');
            return $this->redirectToRoute('app_ville_index');
        }

        return $this->render('ville/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Ville $ville): Response
    {
        return $this->render('ville/show.html.twig', [
            'ville' => $ville,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Ville $ville, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(VilleType::class, $ville);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'La ville a été mise à jour.');
            return $this->redirectToRoute('app_ville_index');
        }

        return $this->render('ville/edit.html.twig', [
            'form' => $form->createView(),
            'ville' => $ville,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Ville $ville, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ville->getId(), (string) $request->request->get('_token'))) {
            $em->remove($ville);
            $em->flush();
            $this->addFlash('success', 'La ville a été supprimée.');
        }

        return $this->redirectToRoute('app_ville_index');
    }
}


