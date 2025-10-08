<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ModifierProfilType;
use App\Repository\ParticipantRepository;
use App\Service\AzureBlobService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

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

    #[Route('/profil/{id}/edit', name: 'app_profile_edit', requirements: ['id' => '\d+'])]
    public function edit(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        AzureBlobService $azure
    ): Response {
        /** @var Participant $participant */
        $participant = $this->getUser();

        $form = $this->createForm(ModifierProfilType::class, $participant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $oldPassword = $form->get('oldPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();

            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            // ✅ Upload Azure
            if ($imageFile) {

                if ($participant->getImageProfil()) {
                    $azure->deleteImage($participant->getImageProfil());
                }

                $blobName = uniqid() . '_' . $imageFile->getClientOriginalName();
                $imagePath = $imageFile->getPathname();

                try {
                    $imageUrl = $azure->uploadImage($imagePath, $blobName);
                    $participant->setImageProfil($imageUrl);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l’envoi de l’image : ' . $e->getMessage());
                }
            }

            // ✅ Gestion du mot de passe
            if ($newPassword) {
                if (!$passwordHasher->isPasswordValid($participant, $oldPassword)) {
                    $this->addFlash('error', 'Ancien mot de passe incorrect ❌');
                    return $this->redirectToRoute('app_profile_edit', ['id' => $participant->getId()]);
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
        ]);
    }
}
