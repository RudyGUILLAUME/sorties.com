<?php

namespace App\DataFixtures;

use App\Entity\Etat;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
class EtatFixtures extends Fixture
{
    public const ETAT_REFERENCE_PREFIX = 'etat-';
    private array $etats = ["Créée","Ouverte","Clôturée","Activité en cours","Passée","Annulée"];
    public function __construct()
    {

    }

    public function load(ObjectManager $manager): void
    {
        for($i = 0; $i < sizeof($this->etats); $i++){
            $etat = new Etat();
            $etat->setLibelle($this->etats[$i]);

            $manager->persist($etat);

            $this->addReference(self::ETAT_REFERENCE_PREFIX . $i, $etat);
        }
        $manager->flush();
    }
}