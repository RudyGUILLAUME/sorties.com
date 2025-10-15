<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ImportCsvType;
use App\Repository\ParticipantRepository;
use App\Repository\SortieRepository;
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
    public function index(
        ParticipantRepository $participantRepo,
        SortieRepository $sortieRepo
    ): Response {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_home');
        }

        // 🔹 KPI globaux
        $kpi = [
            'activeParticipants' => $participantRepo->countActive(),
            'sorties' => $sortieRepo->count([]),
            'participationRate' => $participantRepo->getParticipationRate(),
        ];

        // 🔹 Données pour le graphique
        $result = $sortieRepo->getSortiesCountPerMonth();

        $labels = [];
        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $labels[] = date('M', mktime(0, 0, 0, $m, 1));
            $found = false;
            foreach ($result as $row) {
                if ((int)$row['month'] === $m) {
                    $data[] = (int)$row['sorties_count'];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $data[] = 0;
            }
        }

        $chart = [
            'labels' => $labels,
            'data' => $data,
        ];

        // 🔹 Derniers inscrits (tri par ID décroissant)
        $latestUsers = $participantRepo->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // 🔹 Rendu
        return $this->render('admin/index.html.twig', [
            'kpi' => $kpi,
            'chart' => $chart,
            'latestUsers' => $latestUsers, // ✅ Ajout essentiel
        ]);
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
    public function toggle(Request $request, Participant $participant, EntityManagerInterface $em,SortieRepository $sortieRepository): Response
    {
        if ($this->isCsrfTokenValid('toggle'.$participant->getId(), (string) $request->request->get('_token'))) {

            if($participant->isActif()){
                $participant->setActif(false);
                $message="Le participant a été désactivé.";
                $sorties = $sortieRepository->findBy([]);
                foreach ($sorties as $sortie) {
                    if($sortie->getParticipants()->contains($participant)){
                        $sortie->getParticipants()->removeElement($participant);
                    }
                    if($sortie->getOrganisateur()===$participant){
                        $em->remove($sortie);
                    }
                }
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

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Participant $participant, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$participant->getId(), (string) $request->request->get('_token'))) {
            try{
                $em->remove($participant);
                $em->flush();
                $this->addFlash('success', 'Le participant a été supprimé.');
            } catch (ForeignKeyConstraintViolationException $e) {
                $this->addFlash('error', "⚠️ Impossible de supprimer le participant {$participant->getNom()} car il est encore lié à une ou plusieurs sorties.");
            } catch (\Exception $e) {
                $this->addFlash('error', "❌ Une erreur est survenue lors de la suppression du participant.");
            }
        }

        return $this->redirectToRoute('admin_participants');
    }
}
