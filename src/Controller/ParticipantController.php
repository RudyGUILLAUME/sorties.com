<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/participants', name: 'participants_')]
final class ParticipantController extends AbstractController
{
    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request, ParticipantRepository $repo): JsonResponse
    {
        $query = $request->query->get('q', '');

        $results = $repo->createQueryBuilder('p')
            ->where('p.nom LIKE :query OR p.prenom LIKE :query')
            ->setParameter('query', "%$query%")
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $data = array_map(fn($p) => [
            'id' => $p->getId(),
            'text' => $p->getPrenom().' '.$p->getNom(),
        ], $results);

        return new JsonResponse($data);
    }

    #[Route('/{id}/json', name: 'json', methods: ['GET'])]
    public function getParticipantJson(Participant $participant): JsonResponse
    {
        return $this->json([
            'id' => $participant->getId(),
            'text' => $participant->getPrenom().' '.$participant->getNom(),
        ]);
    }

}