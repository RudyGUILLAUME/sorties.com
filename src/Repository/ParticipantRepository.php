<?php

namespace App\Repository;

use App\Entity\Participant;
use App\Entity\Sortie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<Participant>
 */
class ParticipantRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participant::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Participant) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findByMail($mail): ?Participant
    {
        return $this->createQueryBuilder('participant')
            ->andWhere('participant.mail = :val')
            ->setParameter('val', $mail)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    public function countActive(): int
    {
        return $this->createQueryBuilder('p')
            ->select('count(p.id)')
            ->andWhere('p.actif = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getParticipationRate(): float
    {
        $em = $this->getEntityManager();
        $sortieRepo = $em->getRepository(Sortie::class);

        // Récupérer toutes les sorties
        $sorties = $sortieRepo->findAll();

        if (empty($sorties)) {
            return 0;
        }

        $totalPlaces = 0;
        $totalParticipants = 0;

        foreach ($sorties as $sortie) {
            // Nombre maximum de places pour cette sortie
            $maxPlaces = $sortie->getNbInscriptionsMax();
            $totalPlaces += $maxPlaces;

            // Nombre de participants inscrits
            $participantsCount = $sortie->getParticipants()->count();
            $totalParticipants += $participantsCount;
        }

        // 🔹 Taux = nombre de participants réels / nombre total de places disponibles
        return $totalPlaces > 0 ? ($totalParticipants / $totalPlaces) * 100 : 0;
    }


    //    /**
    //     * @return Participant[] Returns an array of Participant objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Participant
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
