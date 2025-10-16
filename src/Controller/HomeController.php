<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\RegistrationFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        $participant = new Participant();
        $registrationForm = $this->createForm(RegistrationFormType::class, $participant);

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('home/index.html.twig', [
            'registrationForm' => $registrationForm->createView(),
            'error' => $error,
            'last_username' => $lastUsername,
        ]);
    }

}
