<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ModifierProfilType;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ProfileController extends AbstractController
{
    #[Route('/profil/{id}', name: 'app_profile_show', requirements: ['id' => '\d+'])]
    public function show(Participant $participant): Response
    {
        return $this->render('profile/show.html.twig', [
            'participant' => $participant,
        ]);
    }

    #[Route('/profil', name: 'app_profile_me')]
    public function me(): Response
    {
        $this->addFlash('info', 'Veuillez sélectionner un profil.');
        return $this->redirectToRoute('app_home');
    }

    #[Route('/profil/{id}/edit', name: 'app_profile_edit', requirements: ['id' => '\\d+'])]
    public function edit(
        Participant $participant,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger): Response {
        // Access control: allow editing self or any participant if admin
        /** @var Participant $currentUser */
        $currentUser = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        if (!$isAdmin && (!$currentUser || $currentUser->getId() !== $participant->getId())) {
            $this->addFlash('error', 'Accès refusé.');
            if (!$currentUser) {
                return $this->redirectToRoute('app_home');
            }
            return $this->redirectToRoute('app_profile_show', ['id' => $currentUser->getId()]);
        }

        $form = $this->createForm(ModifierProfilType::class, $participant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $oldPassword = $form->get('oldPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();

            $file = $form->get('image_profil')->getData();
            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                try {
                    $file->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Gérer l’erreur
                }

                $participant->setImageProfil($newFilename);

            }


            // ✅ Gestion du mot de passe
            if ($newPassword) {
                // Only require old password when the user edits their own profile (non-admin context)
                $editingSelf = $currentUser && $currentUser->getId() === $participant->getId();
                if ($editingSelf && !$isAdmin) {
                    if (!$passwordHasher->isPasswordValid($participant, $oldPassword)) {
                        $this->addFlash('error', 'Ancien mot de passe incorrect ❌');
                        return $this->redirectToRoute('app_profile_edit', ['id' => $participant->getId()]);
                    }
                }

                $hashedPassword = $passwordHasher->hashPassword($participant, $newPassword);
                $participant->setPassword($hashedPassword);
            }

            $em->persist($participant);
            $em->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès ✅');
            return $this->redirectToRoute('app_profile_show', ['id' => $participant->getId()]);
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
            'participant' => $participant,
        ]);
    }
}
