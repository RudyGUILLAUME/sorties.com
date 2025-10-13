<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Sortie;
use App\Form\CommentaireType;
use App\Repository\EtatRepository;
use App\Service\GestionDateService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/commentaire', name: 'commentaire_')]
final class CommentaireController extends AbstractController
{

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGANISATEUR")'))]
    public function delete(Request $request, Commentaire $commentaire, EntityManagerInterface $em,EtatRepository $etatRepository,GestionDateService $gestionDate): Response
    {
        $id=$commentaire->getSortie()->getId();
        if ($this->isCsrfTokenValid('delete' . $commentaire->getId(), (string) $request->request->get('_token'))) {
            $em->remove($commentaire);
            $em->flush();
            $this->addFlash('success', 'Le commentaire a été supprimé.');
        } else {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_sortie_show',['id' => $id]);
    }


    #[Route('{id}/edit', name: 'edit')]
    public function edit(Commentaire $commentaire, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        // Sécurité : seul l’auteur ou un admin peut modifier
        if (!$user || ($commentaire->getAuteur() !== $user && !$this->isGranted('ROLE_ADMIN'))) {
            $this->addFlash('error', 'Tu ne peux pas modifier ce commentaire.');
            return $this->redirectToRoute('app_sortie_show', ['id' => $commentaire->getSortie()->getId()]);
        }

        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Commentaire modifié avec succès 💬');

            return $this->redirectToRoute('app_sortie_show', [
                'id' => $commentaire->getSortie()->getId(),
            ]);
        }

        return $this->render('commentaire/edit.html.twig', [
            'commentaireForm' => $form->createView(),
            'commentaire' => $commentaire,
        ]);
    }
}
