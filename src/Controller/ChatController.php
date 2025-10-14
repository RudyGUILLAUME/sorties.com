<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\Sortie;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/chat')]
class ChatController extends AbstractController
{
    #[Route('/fetch/{id}', name: 'chat_fetch', methods: ['GET'])]
    public function fetch(
        Sortie $sortie,
        MessageRepository $messageRepository,
        Request $request
    ): JsonResponse {
        $lastId = $request->query->getInt('lastId', 0);

        $qb = $messageRepository->createQueryBuilder('m')
            ->where('m.sortie = :sortie')
            ->setParameter('sortie', $sortie)
            ->orderBy('m.createdAt', 'ASC');

        if ($lastId > 0) {
            $qb->andWhere('m.id > :lastId')
                ->setParameter('lastId', $lastId);
        }

        $messages = $qb->getQuery()->getResult();

        $data = [];
        foreach ($messages as $msg) {
            $data[] = [
                'id' => $msg->getId(),
                'participant' => $msg->getParticipant()->getPrenom(),
                'content' => $msg->getContent(),
                'createdAt' => $msg->getCreatedAt()->format('H:i'),
            ];
        }

        return new JsonResponse($data);
    }


    #[Route('/send/{id}', name: 'chat_send', methods: ['POST'])]
    public function send(Request $request, Sortie $sortie, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $content = trim($data['content'] ?? '');

        if (!$content) {
            return new JsonResponse(['error' => 'Message vide'], 400);
        }

        $message = new Message();
        $message->setContent($content);
        $message->setCreatedAt(new \DateTimeImmutable());
        $message->setParticipant($this->getUser());
        $message->setSortie($sortie);

        $em->persist($message);
        $em->flush();

        return new JsonResponse([
            'participant' => $this->getUser()->getPrenom(),
            'content' => $content,
            'createdAt' => $message->getCreatedAt()->format('H:i'),
        ]);
    }
}
