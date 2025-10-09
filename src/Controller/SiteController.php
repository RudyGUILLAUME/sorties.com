<?php

namespace App\Controller;

use App\Entity\Site;
use App\Form\SiteType;
use App\Repository\SiteRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

 #[IsGranted('ROLE_ADMIN')]
#[Route('/sites', name: 'app_site_')]
final class SiteController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(SiteRepository $siteRepository): Response
    {
        return $this->render('site/index.html.twig', [
            'sites' => $siteRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $site = new Site();
        $form = $this->createForm(SiteType::class, $site);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($site);
            $em->flush();
            $this->addFlash('success', 'Le site a été créé.');
            return $this->redirectToRoute('app_site_index');
        }

        return $this->render('site/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Site $site): Response
    {
        return $this->render('site/show.html.twig', [
            'site' => $site,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Site $site, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(SiteType::class, $site);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Le site a été mis à jour.');
            return $this->redirectToRoute('app_site_index');
        }

        return $this->render('site/edit.html.twig', [
            'form' => $form->createView(),
            'site' => $site,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Site $site, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$site->getId(), (string) $request->request->get('_token'))) {
            try{
                $em->remove($site);
                $em->flush();
                $this->addFlash('success', 'Le site a été supprimé.');
            } catch (ForeignKeyConstraintViolationException $e) {
                $this->addFlash('error', "⚠️ Impossible de supprimer le site {$site->getNom()} car il est encore lié à un ou plusieurs participants/sorties.");
            } catch (\Exception $e) {
                $this->addFlash('error', "❌ Une erreur est survenue lors de la suppression du site.");
            }
        }

        return $this->redirectToRoute('app_site_index');
    }
}


