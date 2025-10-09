<?php

namespace App\DataFixtures;

use App\Entity\Etat;
use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Site;
use App\Entity\Sortie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class SortieFixtures extends Fixture implements DependentFixtureInterface
{
    public const SORTIE_REFERENCE_PREFIX = 'sortie-';
    public function __construct()
    {
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {

        $now = new \DateTime();

        for ($i = 0; $i < 15; $i++) {
            $sortie = new Sortie();
            $sortie->setNom($this->faker->sentence(5));
            $dateDebut = $this->faker->dateTimeBetween('-2 months', '+2 months'); // Date proche pour les tests
            $sortie->setDateHeureDebut($dateDebut);
            $sortie->setDuree($this->faker->time());
            $sortie->setDateLimiteInscription($this->faker->dateTimeBetween('-1 month', $sortie->getDateLimiteInscription()));
            $sortie->setNbInscriptionsMax($this->faker->numberBetween(1, 30));
            $sortie->setInfosSortie($this->faker->paragraph(2));

            // Affecter lieu, site, organisateur
            $num_lieu=mt_rand(0,9);
            $sortie->setLieu($this->getReference(LieuFixtures::LIEU_REFERENCE_PREFIX . $num_lieu, Lieu::class));
            $num_site=mt_rand(0,9);
            $sortie->setSite($this->getReference(SiteFixtures::SITE_REFERENCE_PREFIX . $num_site, Site::class));
            $num_etat=mt_rand(0,5);
            $num_organisateur=mt_rand(0,9);
            $sortie->setOrganisateur($this->getReference(ParticipantFixtures::PARTICIPANT_REFERENCE_PREFIX . $num_organisateur, Participant::class));

            // Si la date de début est passée, on force l'état à "Clôturée"
            if ($dateDebut < $now) {
                $etatCloturee = $manager->getRepository(Etat::class)->findOneBy(['libelle' => 'Clôturée']);
                $sortie->setEtat($etatCloturee);
            } else {
                $sortie->setEtat($this->getReference(EtatFixtures::ETAT_REFERENCE_PREFIX . $num_etat, Etat::class));
            }


            $used = [];
            $maxPlaces = $sortie->getNbInscriptionsMax();
            $nbParticipants = mt_rand(2, min(8, $maxPlaces)); // 🔒 max = nb max d'inscriptions

            for ($j = 0; $j < $nbParticipants; $j++) {
                do {
                    $index = mt_rand(0, 9);
                } while (in_array($index, $used));

                $used[] = $index;
                $participant = $this->getReference(ParticipantFixtures::PARTICIPANT_REFERENCE_PREFIX . $index, Participant::class);
                $sortie->addParticipant($participant);
            }
            $manager->persist($sortie);

            $this->addReference(self::SORTIE_REFERENCE_PREFIX . $i, $sortie);
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            EtatFixtures::class,
            VilleFixtures::class,
            LieuFixtures::class,
            SiteFixtures::class,
            ParticipantFixtures::class,
        ];
    }
}

