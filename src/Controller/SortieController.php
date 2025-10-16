<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Etat;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\CommentaireType;
use App\Form\SortieType;
use App\Repository\MessageRepository;
use App\Repository\ParticipantRepository;
use App\Service\GestionDateService;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\EtatRepository;
use App\Repository\SortieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/sorties', name: 'app_sortie_')]
final class SortieController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(SortieRepository $sortieRepository, EntityManagerInterface $em, EtatRepository $etatRepository, Request $request,GestionDateService $gestionDate, MessageRepository $messageRepository): Response
    {
        $tab = $request->query->get('tab', 'toutes');
        $sorties = $sortieRepository->findBy([], ['dateHeureDebut' => 'DESC']);
        $participant = $this->getUser();

        $now = (new \DateTime('now', new \DateTimeZone('Europe/Paris')));
        $archivageDate = (clone $now)->modify('-1 month');

        $sortiesDisponibles = $sortieRepository->findDisponibles($now);
        $sortiesPleines = $sortieRepository->findPleines($now);
        $sortiesArchivees = $sortieRepository->findArchivees($archivageDate);

        // Build and handle search form
        $form = $this->createForm(\App\Form\SortieSearchType::class, null, [
            'method' => 'GET'
        ]);
        $form->handleRequest($request);
        $criteria = $form->getData() ?? [];

        $sorties = $sortieRepository->search($criteria, $participant?->getId());

        $sorties_visibles = array_filter($sorties, function ($sortie) use ($participant) {
            if (!$sortie->isPrivee()) return true;
            if ($sortie->getOrganisateur() === $participant) return true;
            return $sortie->getInvites()->contains($participant);
        });

        foreach ($sorties_visibles as $sortie) {
            $gestionDate->GestionDate($em,$etatRepository,$sortie);
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
            'sorties' => $sorties_visibles,
            'participant' => $participant,
            'searchForm' => $form->createView(),
            'sortiesDisponibles' => $sortiesDisponibles,
            'sortiesPleines' => $sortiesPleines,
            'sortiesArchivees' => $sortiesArchivees,
            'activeTab' => $tab,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGANISATEUR")'))]
    public function new(Request $request, EntityManagerInterface $em, EtatRepository $etatRepository,GestionDateService $gestionDate, SluggerInterface $slugger, ParticipantRepository $participantRepository): Response
    {
        $participant = $this->getUser();
        $sortie = new Sortie();

        if(!$participant->isActif()){
            $this->addFlash('error', 'Votre compte est désactivé, vous ne pouvez pas créer de sortie.');
            return $this->redirectToRoute('app_home');
        }

        // Préremplissage si besoin
        $sortie->setDateHeureDebut(new \DateTime('+1 day',new \DateTimeZone('Europe/Paris')));
        $sortie->setDateLimiteInscription(new \DateTime('+12 hours',new \DateTimeZone('Europe/Paris')));
        $sortie->setOrganisateur($participant);

        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        $invitesIds = explode(',', $form->get('invites')->getData() ?? '');
        foreach ($invitesIds as $id) {
            $participant = $participantRepository->find($id);
            if ($participant) {
                $sortie->addInvite($participant);
            }
        }



        if ($form->isSubmitted() && $form->isValid()) {
            // Définir l’état par défaut à "En création"
            $etat = $etatRepository->findOneBy(['libelle' => 'Créée']) ?? $etatRepository->findOneBy([]);
            if ($etat) {
                $sortie->setEtat($etat);
            }

            $imageFile = $form->get('image_principale')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('sorties_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Gestion d’erreur (log, message flash, etc.)
                }

                $sortie->setImagePrincipale($newFilename);
            }

            $gestionDate->GestionDate($em,$etatRepository,$sortie);

            $em->flush();

            $this->addFlash('success', 'La sortie a été créée avec succès.');
            return $this->redirectToRoute('app_sortie_index');
        }

        return $this->render('sortie/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET','POST'])]
    public function show(Request $request, EntityManagerInterface $em, Sortie $sortie, MessageRepository $messageRepository): Response
    {
        if ($sortie->isPrivee() &&
            $sortie->getOrganisateur() !== $this->getUser() &&
            !$sortie->getInvites()->contains($this->getUser())) {

            $this->addFlash('error', 'Tu n’as pas accès à cette sortie');
            return $this->redirectToRoute('app_sortie_index');
        }

        $commentaire = new Commentaire();
        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        $messages = $messageRepository->findBy(
            ['sortie' => $sortie],
            ['createdAt' => 'ASC']
        );

        $etat = $sortie->getEtat();
        $participant = $sortie->getParticipants();

        if ($form->isSubmitted() && $form->isValid()) {
            $commentaire->setAuteur($this->getUser());
            $commentaire->setSortie($sortie);

            $em->persist($commentaire);
            $em->flush();

            $this->addFlash('success', 'Commentaire ajouté avec succès !');
            return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
        }

        if (!$sortie->getCommentaires()->isEmpty()){
            $total = 0;
            foreach ($sortie->getCommentaires() as $c) {
                $total += $c->getNote();
            }
        }

        return $this->render('sortie/show.html.twig', [
            'sortie' => $sortie,
            'commentaireForm' => $form->createView(),
            'noteMoyenne' =>count($sortie->getCommentaires())!=0?$total / count($sortie->getCommentaires()):null,
            'messages' => $messages,
            'etat' => $etat,
            'participant' => $participant
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGANISATEUR")'))]
    public function edit(Request $request, Sortie $sortie, EntityManagerInterface $em, EtatRepository $etatRepository,GestionDateService $gestionDate, SluggerInterface $slugger, ParticipantRepository $participantRepository): Response
    {
        $gestionDate->GestionDate($em,$etatRepository,$sortie);

        // Bloquer l'édition si l'état n'est pas "Créée"
        if ($sortie->getEtat()->getLibelle() !== 'Créée') {
            $this->addFlash('danger', 'Vous ne pouvez modifier cette sortie que si elle est en création.');
            return $this->redirectToRoute('app_sortie_index');
        }
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $raw = (string) $form->get('invites')->getData();
            $ids = array_filter(array_map('trim', explode(',', $raw)), fn($v) => $v !== '');

            // optional: cast to int and unique
            $ids = array_values(array_unique(array_map('intval', $ids)));

            foreach ($sortie->getInvites() as $invite) {
                $sortie->removeInvite($invite);
            }

            // Ajouter les nouveaux invités
            foreach ($ids as $id) {
                $participant = $participantRepository->find($id);
                if ($participant) {
                    $sortie->addInvite($participant);
                }
            }

            $imageFile = $form->get('image_principale')->getData();

            if ($imageFile) {
                $filesystem = new Filesystem();

                if ($sortie->getImagePrincipale()) {
                    $oldImagePath = $this->getParameter('sorties_images_directory') . '/' . $sortie->getImagePrincipale();
                    if ($filesystem->exists($oldImagePath)) {
                        $filesystem->remove($oldImagePath);
                    }
                }

                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('sorties_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                }

                $sortie->setImagePrincipale($newFilename);
            }
            $em->persist($sortie);
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
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGANISATEUR")'))]
    public function delete(Request $request, Sortie $sortie, EntityManagerInterface $em,EtatRepository $etatRepository,GestionDateService $gestionDate): Response
    {
        $gestionDate->GestionDate($em,$etatRepository,$sortie);
        // Vérifier que l'état est "Créée" avant suppression
        if ($sortie->getEtat()->getLibelle() !== 'Créée') {
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
    public function subscribe(Sortie $sortie, EntityManagerInterface $em, Request $request,GestionDateService $gestionDate, EtatRepository $etatRepository,MailService $mailService): Response
    {
        $participant = $this->getUser();

        if(!$participant->isActif()){
            $this->addFlash('error', 'Votre compte est désactivé, vous ne pouvez pas vous inscrire.');
            return $this->redirectToRoute('app_home');
        }

        if (!$this->isCsrfTokenValid('subscribe' . $sortie->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $gestionDate->GestionDate($em,$etatRepository,$sortie);

        // Bloquer inscription si sortie non ouverte (Annulée, Clôturée, etc.)
        if ($sortie->getEtat()->getLibelle() !== 'Ouverte') {
            $this->addFlash('danger', 'Vous ne pouvez pas vous inscrire à une sortie qui n’est pas ouverte.');
            return $this->redirectToRoute('app_sortie_index');
        }

        if($sortie->isPrivee()&&!$sortie->getInvites()->contains($participant)){
            $this->addFlash('danger', "Vous ne pouvez pas vous inscrire à une sortie privée à laquelle vous n'êtes pas invités.");
            return $this->redirectToRoute('app_sortie_index');
        }

        if (!$sortie->getParticipants()->contains($participant)) {
            $sortie->addParticipant($participant);
            $em->flush();
            $this->addFlash('success', 'Inscription réussie !');
        }

        $mailService->send($participant->getMail(), 'Inscription confirmée', sprintf(
                '<p>Bonjour %s %s,</p><p>Tu es bien inscrit à la sortie <strong>%s</strong> prévue le <strong>%s</strong>.</p>',
                $participant->getPrenom(),
                $participant->getNom(),
                $sortie->getNom(),
                $sortie->getDateHeureDebut()->format('d/m/Y H:i:s')
            )
        );

        //Redirection vers onglet Disponibles
        $tab = $request->query->get('tab', 'disponibles');
        return $this->redirectToRoute('app_sortie_index', ['tab' => $tab]);
    }


    #[Route('/{id}/unsubscribe', name: 'unsubscribe', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function unsubscribe(Sortie $sortie, EntityManagerInterface $em, Request $request, EtatRepository $etatRepository,GestionDateService $gestionDate,MailService $mailService): Response
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

        $gestionDate->GestionDate($em,$etatRepository,$sortie);

        // Autoriser désinscription si inscrit, même si la sortie est "Clôturée"
        if (!in_array($sortie->getEtat()->getLibelle(), ['Ouverte', 'Créée', 'Clôturée'])) {
            $this->addFlash('danger', 'Vous ne pouvez plus vous désinscrire de cette sortie.');
            return $this->redirectToRoute('app_sortie_index');
        }

        // 🔒 Si la sortie est "Clôturée" ET que la date limite d’inscription est dépassée
        if (
            $sortie->getEtat()->getLibelle() === 'Clôturée' &&
            $sortie->getDateLimiteInscription()->format("Y-m-d H:i:s") < (new \DateTime('now', new \DateTimeZone('Europe/Paris')))->format("Y-m-d H:i:s")
        ) {
            $this->addFlash('danger', 'Vous ne pouvez plus vous désinscrire : la date limite est dépassée.');

            //Redirection vers onglet Disponibles
            $tab = $request->query->get('tab', 'disponibles');
            return $this->redirectToRoute('app_sortie_index', ['tab' => $tab]);        }

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

        $mailService->send($participant->getMail(), 'Désinscription confirmée', sprintf(
                '<p>Bonjour %s %s,</p><p>Tu es bien désinscrit de la sortie <strong>%s</strong> prévue le <strong>%s</strong>.</p>',
                $participant->getPrenom(),
                $participant->getNom(),
                $sortie->getNom(),
                $sortie->getDateHeureDebut()->format('d/m/Y H:i:s')
            )
        );

        return $this->redirectToRoute('app_sortie_index');
    }


    #[Route('/{id}/publish', name: 'publish', methods: ['POST'])]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGANISATEUR")'))]
    public function publish(Sortie $sortie, EtatRepository $etatRepository, EntityManagerInterface $em,GestionDateService $gestionDate): Response
    {
        $gestionDate->GestionDate($em,$etatRepository,$sortie);
        if ($sortie->getEtat()->getLibelle() !== 'Créée') {
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
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGANISATEUR")'))]
    public function cancel(Sortie $sortie, EtatRepository $etatRepository, EntityManagerInterface $em,GestionDateService $gestionDate): Response
    {
        $gestionDate->GestionDate($em,$etatRepository,$sortie);
        // Seul l'organisateur peut annuler
        if ($this->getUser() !== $sortie->getOrganisateur()&&!(in_array($this->isGranted('ROLE_ADMIN'), $this->getUser()->getRoles()))) {
            $this->addFlash('danger', 'Seul l\'organisateur ou un admin peut annuler la sortie.');
            return $this->redirectToRoute('app_sortie_index');
        }

        // Si déjà annulée, ou clôturée, etc. on bloque
        $etat = $sortie->getEtat()->getLibelle();
        if (in_array($etat, ['Annulée', 'Créée', 'Passée','Activité en cours'])) {
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
