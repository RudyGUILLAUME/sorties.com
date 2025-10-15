<?php

namespace App\Service;



use App\Entity\Sortie;
use App\Repository\EtatRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;

class GestionDateService
{
    public function __construct(private readonly SortieRepository $sortieRepository)
    {
    }

    public function GestionDate(EntityManagerInterface $em,EtatRepository $etatRepository, Sortie $sortie){
        $now=(new \DateTime('now', new \DateTimeZone('Europe/Paris')))->format('Y-m-d H:i:s');

        if(($sortie->getEtat()=="Créée"||$sortie->getEtat())=="Ouverte"&&$sortie->getDateLimiteInscription()->format('Y-m-d H:i:s') < $now){
            $sortie->setEtat($etatRepository->findOneBy(['libelle' => 'Clôturée']));
        }

        if($sortie->getEtat()=="Clôturée"&&$sortie->getDateHeureDebut()->format("Y-m-d H:i:s") > $now){
            $sortie->setEtat($etatRepository->findOneBy(['libelle' => 'Activité en cours']));
        }

        $dateFin = (clone $sortie->getDateHeureDebut())->modify("+{$sortie->getDuree()} minutes");
        if($sortie->getEtat()=="Activité en cours"&&$dateFin->format("Y-m-d H:i:s") < $now){
            $sortie->setEtat($etatRepository->findOneBy(['libelle' => 'Passée']));
        }

        $em->persist($sortie);
    }
}