<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\SortieType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\EtatRepository;
use App\Repository\SortieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/sorties', name: 'app_sortie_')]
final class SortieController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(SortieRepository $sortieRepository, EntityManagerInterface $em, EtatRepository $etatRepository): Response
    {
        $sorties = $sortieRepository->findBy([], ['dateHeureDebut' => 'DESC']);
        $participant = $this->getUser();

        $now = new \DateTime();
        $archivageDate = (clone $now)->modify('-1 month');

        $sortiesDisponibles = $sortieRepository->findDisponibles($now);
        $sortiesPleines = $sortieRepository->findPleines($now);
        $sortiesArchivees = $sortieRepository->findArchivees($archivageDate);

        foreach ($sorties as $sortie) {
            $etatActuel = $sortie->getEtat()->getLibelle();

            // Nombre max atteint ou date limite dépassée
            if (
                ($sortie->getDateLimiteInscription() < $now ||
                    count($sortie->getParticipants()) >= $sortie->getNbInscriptionsMax())
                && $etatActuel === 'Ouverte'
            ) {
                $etatCloturee = $etatRepository->findOneBy(['libelle' => 'Clôturée']);
                $sortie->setEtat($etatCloturee);
                $em->persist($sortie);
            }

        }
        $em->flush();

        return $this->render('sortie/index.html.twig', [
            'sorties' => $sorties,
            'participant' => $participant,
            'sortiesDisponibles' => $sortiesDisponibles,
            'sortiesPleines' => $sortiesPleines,
            'sortiesArchivees' => $sortiesArchivees,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function new(Request $request, EntityManagerInterface $em, EtatRepository $etatRepository): Response
    {
        $participant = $this->getUser();
        $sortie = new Sortie();

        // Préremplissage si besoin
        $sortie->setDateHeureDebut(new \DateTime('+1 day'));
        $sortie->setDateLimiteInscription(new \DateTime('+12 hours'));
        $sortie->setOrganisateur($participant);

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

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Sortie $sortie): Response
    {
        return $this->render('sortie/show.html.twig', [
            'sortie' => $sortie,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function edit(Request $request, Sortie $sortie, EntityManagerInterface $em): Response
    {
        // Bloquer l'édition si l'état n'est pas "En création"
        if ($sortie->getEtat()->getLibelle() !== 'En création') {
            $this->addFlash('danger', 'Vous ne pouvez modifier cette sortie que si elle est en création.');
            return $this->redirectToRoute('app_sortie_index');
        }

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

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function delete(Request $request, Sortie $sortie, EntityManagerInterface $em): Response
    {
        // Vérifier que l'état est "En création" avant suppression
        if ($sortie->getEtat()->getLibelle() !== 'En création') {
            $this->addFlash('danger', 'Impossible de supprimer une sortie publiée ou clôturée.');
            return $this->redirectToRoute('app_sortie_index');
        }

        if ($this->isCsrfTokenValid('delete' . $sortie->getId(), (string) $request->request->get('_token'))) {
            $em->remove($sortie);
            $em->flush();
            $this->addFlash('success', 'La sortie a été supprimée.');
        } else {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_sortie_index');
    }

    #[Route('/{id}/subscribe', name: 'subscribe', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function subscribe(Sortie $sortie, EntityManagerInterface $em, Request $request): Response
    {
        $participant = $this->getUser();

        if (!$this->isCsrfTokenValid('subscribe' . $sortie->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        // Bloquer inscription si sortie non ouverte (Annulée, Clôturée, etc.)
        if ($sortie->getEtat()->getLibelle() !== 'Ouverte') {
            $this->addFlash('danger', 'Vous ne pouvez pas vous inscrire à une sortie qui n’est pas ouverte.');
            return $this->redirectToRoute('app_sortie_index');
        }

        if (!$sortie->getParticipants()->contains($participant)) {
            $sortie->addParticipant($participant);
            $em->flush();
            $this->addFlash('success', 'Inscription réussie !');
        }

        return $this->redirectToRoute('app_sortie_index');
    }


    #[Route('/{id}/unsubscribe', name: 'unsubscribe', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function unsubscribe(Sortie $sortie, EntityManagerInterface $em, Request $request, EtatRepository $etatRepository): Response
    {
        $participant = $this->getUser();

        if (!$this->isCsrfTokenValid('unsubscribe' . $sortie->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        // Vérifie que l'utilisateur est inscrit
        if (!$sortie->getParticipants()->contains($participant)) {
            $this->addFlash('danger', 'Vous n’êtes pas inscrit à cette sortie.');
            return $this->redirectToRoute('app_sortie_index');
        }

        // Autoriser désinscription si inscrit, même si la sortie est "Clôturée"
        if (!in_array($sortie->getEtat()->getLibelle(), ['Ouverte', 'En création', 'Clôturée'])) {
            $this->addFlash('danger', 'Vous ne pouvez plus vous désinscrire de cette sortie.');
            return $this->redirectToRoute('app_sortie_index');
        }

        // Désinscription
        $sortie->removeParticipant($participant);

        // Vérifie si la sortie était "Clôturée" uniquement parce qu'elle était pleine
        if (
            $sortie->getEtat()->getLibelle() === 'Clôturée' &&
            count($sortie->getParticipants()) < $sortie->getNbInscriptionsMax()
        ) {
            $etatOuverte = $etatRepository->findOneBy(['libelle' => 'Ouverte']);
            if ($etatOuverte) {
                $sortie->setEtat($etatOuverte);
            }
        }

        $em->flush();
        $this->addFlash('success', 'Désinscription réussie !');

        return $this->redirectToRoute('app_sortie_index');
    }


    #[Route('/{id}/publish', name: 'publish', methods: ['POST'])]
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function publish(Sortie $sortie, EtatRepository $etatRepository, EntityManagerInterface $em): Response
    {
        if ($sortie->getEtat()->getLibelle() !== 'En création') {
            $this->addFlash('warning', 'La sortie ne peut pas être publiée.');
            return $this->redirectToRoute('app_sortie_index');
        }

        $etatOuverte = $etatRepository->findOneBy(['libelle' => 'Ouverte']);
        $sortie->setEtat($etatOuverte);
        $em->flush();

        $this->addFlash('success', 'Sortie publiée avec succès.');
        return $this->redirectToRoute('app_sortie_index');
    }

    #[Route('/{id}/cancel', name: 'cancel', methods: ['POST'])]
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function cancel(Sortie $sortie, EtatRepository $etatRepository, EntityManagerInterface $em): Response
    {
        // Seul l'organisateur peut annuler
        if ($this->getUser() !== $sortie->getOrganisateur()) {
            $this->addFlash('danger', 'Seul l\'organisateur peut annuler la sortie.');
            return $this->redirectToRoute('app_sortie_index');
        }

        // Si déjà annulée, ou clôturée, etc. on bloque
        $etat = $sortie->getEtat()->getLibelle();
        if (in_array($etat, ['Annulée', 'Clôturée', 'Passée'])) {
            $this->addFlash('warning', 'Cette sortie ne peut pas être annulée.');
            return $this->redirectToRoute('app_sortie_index');
        }

        $etatAnnulee = $etatRepository->findOneBy(['libelle' => 'Annulée']);
        $sortie->setEtat($etatAnnulee);
        $em->flush();

        $this->addFlash('success', 'La sortie a été annulée avec succès.');
        return $this->redirectToRoute('app_sortie_index');
    }

}
