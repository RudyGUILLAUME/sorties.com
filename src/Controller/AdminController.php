<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ImportCsvType;
use App\Repository\ParticipantRepository;
use App\Repository\SiteRepository;
use App\Service\FichierCSVService;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin', name: 'admin_')]
final class AdminController extends AbstractController
{
    #[Route('', name: 'panel')]
    public function index(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('admin/index.html.twig');
    }

    #[Route('/import', name: 'import_csv')]
    public function importCsv(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher,FichierCSVService $csv): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(ImportCsvType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('csv_file')->getData();

            if ($file) {
                $return=$csv->createUsers($file->getPathname(),$em,$passwordHasher);

                if (!empty($return[1])) {
                    $this->addFlash('warning', implode(' ', $return[1]));
                }

                $this->addFlash('success', "$return[0] utilisateurs importés avec succès !");
                return $this->redirectToRoute('admin_import_csv');
            }
        }
        return $this->render('admin/import.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/participants', name: 'participants')]
    public function participants(ParticipantRepository $participantRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('admin/participants.html.twig', [
            'participants' => $participantRepository->findAll(),
        ]);
    }

    #[Route('/{id}/toggle', name: 'toggle', methods: ['POST'])]
    public function toggle(Request $request, Participant $participant, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('toggle'.$participant->getId(), (string) $request->request->get('_token'))) {

            if($participant->isActif()){
                $participant->setActif(false);
                $message="Le participant a été désactivé.";
            }
            else{
                $participant->setActif(true);
                $message="Le participant a été activé.";
            }
            $em->persist($participant);
            $em->flush();
            $this->addFlash('success', $message);
        }

        return $this->redirectToRoute('admin_participants');
    }
}
