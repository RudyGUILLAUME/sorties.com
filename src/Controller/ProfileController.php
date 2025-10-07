<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController
{
    #[Route('/profil/{id}', name: 'app_profile_show', requirements: ['id' => '\\d+'])]
    public function show(Participant $participant): Response
    {
        return $this->render('profile/show.html.twig', [
            'participant' => $participant,
        ]);
    }

    #[Route('/profil', name: 'app_profile_me')]
    public function me(ParticipantRepository $participantRepository): Response
    {
        $this->addFlash('info', 'Veuillez sélectionner un profil.');
        return $this->redirectToRoute('app_home');
    }
}
