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

    /**
     * Trouve les sorties disponibles (places restantes, pas archivées)
     *
     * @param \DateTimeInterface $now
     * @return Sortie[]
     */
    public function findDisponibles(\DateTimeInterface $now): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.etat', 'e')
            ->andWhere('s.dateHeureDebut > :now')
            ->andWhere('SIZE(s.participants) < s.nbInscriptionsMax')
            ->andWhere('e.libelle = :etatLibelle')
            ->setParameter('now', $now)
            ->setParameter('etatLibelle', 'Ouverte')
            ->orderBy('s.dateHeureDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les sorties pleines (participants >= max)
     *
     * @param \DateTimeInterface $now
     * @return Sortie[]
     */
    public function findPleines(\DateTimeInterface $now): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.dateHeureDebut > :now')
            ->andWhere('SIZE(s.participants) >= s.nbInscriptionsMax')
            ->setParameter('now', $now)
            ->orderBy('s.dateHeureDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les sorties archivées (plus d'un mois après la date)
     *
     * @param \DateTimeInterface $archiveDate
     * @return Sortie[]
     */
    public function findArchivees(\DateTimeInterface $archiveDate): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.dateHeureDebut <= :archiveDate')
            ->setParameter('archiveDate', $archiveDate)
            ->orderBy('s.dateHeureDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de sorties organisées par un participant donné
     */
    public function countByOrganisateur(int $participantId): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.organisateur = :pid')
            ->setParameter('pid', $participantId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte le nombre de sorties auxquelles un participant a participé
     */
    public function countByParticipant(int $participantId): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->join('s.participants', 'p')
            ->andWhere('p.id = :pid')
            ->setParameter('pid', $participantId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Retourne l'organisateur préféré d'un participant (celui dont il a suivi le plus de sorties)
     */
    public function findOrganisateurPrefere(int $participantId): ?array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('o.id, o.prenom, o.nom, COUNT(s.id) AS sorties_count')
            ->join('s.organisateur', 'o')
            ->join('s.participants', 'p')
            ->andWhere('p.id = :pid')
            ->setParameter('pid', $participantId)
            ->groupBy('o.id')
            ->orderBy('sorties_count', 'DESC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Retourne le site préféré d'un participant (le site où il a participé le plus)
     */
    public function findSitePrefere(int $participantId): ?array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('site.id, site.nom, COUNT(s.id) AS sorties_count')
            ->join('s.site', 'site')
            ->join('s.participants', 'p')
            ->andWhere('p.id = :pid')
            ->setParameter('pid', $participantId)
            ->groupBy('site.id')
            ->orderBy('sorties_count', 'DESC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
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
