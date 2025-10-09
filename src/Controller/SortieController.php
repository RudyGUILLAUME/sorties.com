<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Form\SortieType;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\EtatRepository;
use App\Repository\SortieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class SortieController extends AbstractController
{
    #[Route('/sorties', name: 'app_sortie_index', methods: ['GET'])]
    public function index(Request $request, SortieRepository $sortieRepository, SiteRepository $siteRepository): Response
    {
        $q = $request->query->get('q');
        $siteId = $request->query->get('site');
        $dateDebut = $request->query->get('dateDebut');
        $dateFin = $request->query->get('dateFin');

        // conversions
        $siteId = $siteId ? (int) $siteId : null;
        $dateDebut = $dateDebut ? new \DateTime($dateDebut) : null;
        $dateFin = $dateFin ? new \DateTime($dateFin) : null;

        $sorties = $sortieRepository->findByFilters($q, $siteId, $dateDebut, $dateFin);

        return $this->render('sortie/index.html.twig', [
            'sorties' => $sorties,
            'sites' => $siteRepository->findAll(),
        ]);
    }





    #[Route('/sorties/new', name: 'app_sortie_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, EtatRepository $etatRepository): Response
    {

        $sortie = new Sortie();

        // Préremplissage si besoin
        $sortie->setDateHeureDebut(new \DateTime('+1 day'));
        $sortie->setDateLimiteInscription(new \DateTime('+12 hours'));
        $sortie->setOrganisateur($user); // 👈 Définir l’organisateur connecté Besoin authentification

        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Définir l’état par défaut à "En création"
            $etat = $etatRepository->findOneBy(['libelle' => 'En création']) ?? $etatRepository->findOneBy([]);
            if ($etat) {
                $sortie->setEtat($etat);
            }

            $em->persist($sortie);
            $em->flush();

            $this->addFlash('success', 'La sortie a été créée avec succès.');
            return $this->redirectToRoute('app_sortie_index');
        }

        return $this->render('sortie/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/sorties/{id}', name: 'app_sortie_show', methods: ['GET'])]
    public function show(Sortie $sortie): Response
    {
        return $this->render('sortie/show.html.twig', [
            'sortie' => $sortie,
        ]);
    }

    #[Route('/sorties/{id}/edit', name: 'app_sortie_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Sortie $sortie, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'La sortie a été mise à jour.');
            return $this->redirectToRoute('app_sortie_index');
        }

        return $this->render('sortie/edit.html.twig', [
            'form' => $form->createView(),
            'sortie' => $sortie,
        ]);
    }

    #[Route('/sorties/{id}', name: 'app_sortie_delete', methods: ['POST'])]
    public function delete(Request $request, Sortie $sortie, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$sortie->getId(), (string) $request->request->get('_token'))) {
            $em->remove($sortie);
            $em->flush();
            $this->addFlash('success', 'La sortie a été supprimée.');
        }

        return $this->redirectToRoute('app_sortie_index');
    }
}
