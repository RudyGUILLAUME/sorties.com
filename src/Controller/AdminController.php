<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ImportCsvType;
use App\Repository\SiteRepository;
use App\Service\FichierCSVService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_panel')]
    public function index(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('admin/index.html.twig');
    }

    #[Route('/admin/import', name: 'admin_import_csv')]
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
                $count=$csv->createUsers($file->getPathname(),$em,$passwordHasher);

                $this->addFlash('success', "$count utilisateurs importés avec succès !");
                return $this->redirectToRoute('admin_import_csv');
            }
        }

        return $this->render('admin/import.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
