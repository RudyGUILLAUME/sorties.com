<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Form\SortieType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\EtatRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class SortieController extends AbstractController
{
    #[Route('/sorties/creer', name: 'app_sortie_create')]
    public function create(Request $request, EntityManagerInterface $em, EtatRepository $etatRepository): Response
    {
        $sortie = new Sortie();

        // Pré-remplir éventuellement des valeurs par défaut
        $sortie->setDateHeureDebut(new \DateTime('+1 day'));
        $sortie->setDateLimiteInscription(new \DateTime('+12 hours'));

        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Définir l'organisateur comme l'utilisateur connecté, si Participant est l'utilisateur
            if (method_exists($this->getUser(), 'getParticipant')) {
                $sortie->setOrganisateur($this->getUser()->getParticipant());
            } elseif ($this->getUser() instanceof \App\Entity\Participant) {
                $sortie->setOrganisateur($this->getUser());
            }

            // Etat par défaut
            $etatCree = $etatRepository->findOneBy(['libelle' => 'Créée']) ?? $etatRepository->findOneBy([]);
            if ($etatCree) {
                $sortie->setEtat($etatCree);
            }

            $em->persist($sortie);
            $em->flush();

            $this->addFlash('success', 'La sortie a été créée avec succès.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('sortie/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
