<?php

namespace App\Repository;

use App\Entity\Sortie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sortie>
 */
class SortieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);
    }

    /**
     * Search sorties with filters.
     * @param array $criteria
     * @param int|null $currentParticipantId
     * @return Sortie[]
     */
    public function search(array $criteria, ?int $currentParticipantId): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.organisateur', 'o')->addSelect('o')
            ->leftJoin('s.etat', 'e')->addSelect('e')
            ->leftJoin('s.participants', 'p')->addSelect('p')
            ->leftJoin('s.site', 'site')->addSelect('site');

        if (!empty($criteria['q'])) {
            $qb->andWhere('LOWER(s.nom) LIKE :q')
               ->setParameter('q', '%' . mb_strtolower($criteria['q']) . '%');
        }
        if (!empty($criteria['site'])) {
            $qb->andWhere('site = :site')
               ->setParameter('site', $criteria['site']);
        }
        if (!empty($criteria['dateMin'])) {
            $qb->andWhere('s.dateHeureDebut >= :dmin')
               ->setParameter('dmin', $criteria['dateMin']);
        }
        if (!empty($criteria['dateMax'])) {
            // include the full day for max
            $dateMax = clone $criteria['dateMax'];
            $dateMax->setTime(23,59,59);
            $qb->andWhere('s.dateHeureDebut <= :dmax')
               ->setParameter('dmax', $dateMax);
        }
        if (!empty($criteria['organisateur']) && $currentParticipantId) {
            $qb->andWhere('o.id = :oid')
               ->setParameter('oid', $currentParticipantId);
        }
        if (!empty($criteria['inscrit']) && $currentParticipantId) {
            $qb->andWhere(':pid MEMBER OF s.participants')
               ->setParameter('pid', $currentParticipantId);
        }
        if (!empty($criteria['nonInscrit']) && $currentParticipantId) {
            $qb->andWhere(':pid2 NOT MEMBER OF s.participants')
               ->setParameter('pid2', $currentParticipantId);
        }
        if (!empty($criteria['passees'])) {
            $qb->andWhere('s.dateHeureDebut < :now')
               ->setParameter('now', new \DateTime());
        } else {
            // by default show upcoming and ongoing
        }

        return $qb->orderBy('s.dateHeureDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Fetch all sorties with organizer, etat and participants to avoid N+1.
     *
     * @return Sortie[]
     */
    public function findAllWithRelations(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.organisateur', 'o')->addSelect('o')
            ->leftJoin('s.etat', 'e')->addSelect('e')
            ->leftJoin('s.participants', 'p')->addSelect('p')
            ->orderBy('s.dateHeureDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Fetch sorties where the given participant is registered, with needed relations.
     *
     * @return Sortie[]
     */
    public function findForParticipantWithRelations(int $participantId): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.organisateur', 'o')->addSelect('o')
            ->leftJoin('s.etat', 'e')->addSelect('e')
            ->leftJoin('s.participants', 'p')->addSelect('p')
            ->andWhere('p.id = :pid')
            ->setParameter('pid', $participantId)
            ->orderBy('s.dateHeureDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }
    //    /**
    //     * @return Sortie[] Returns an array of Sortie objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Sortie
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
