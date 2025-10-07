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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ParticipantFixtures extends Fixture implements DependentFixtureInterface
{
    public const PARTICIPANT_REFERENCE_PREFIX = 'participant-';
    public function __construct(private UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        $participant = new Participant ();
        $participant->setNom("Admin");
        $participant->setPrenom("Admin");
        $participant->setTelephone("0123456789");
        $participant->setMail("admin@mail.fr");
        $participant->setAdministrateur(true);
        $participant->setActif(true);
        $participant->isVerified(true);
        $participant->setPassword($this->userPasswordHasher->hashPassword($participant, 'mdpadmin'));
        $participant->setRoles(['ROLE_USER','ROLE_ADMIN']);
        $num_site=mt_rand(0,9);
        $participant->setSite($this->getReference(SiteFixtures::SITE_REFERENCE_PREFIX . $num_site, Site::class));
        $manager->persist($participant);

        $this->addReference(self::PARTICIPANT_REFERENCE_PREFIX . 0, $participant);

        $participant = new Participant ();
        $participant->setNom("User");
        $participant->setPrenom("User");
        $participant->setTelephone("0129256789");
        $participant->setMail("user@mail.fr");
        $participant->setAdministrateur(false);
        $participant->setActif(true);
        $participant->isVerified(true);
        $participant->setPassword($this->userPasswordHasher->hashPassword($participant, 'mdpuser'));
        $participant->setRoles(['ROLE_USER']);
        $num_site=mt_rand(0,9);
        $participant->setSite($this->getReference(SiteFixtures::SITE_REFERENCE_PREFIX . $num_site, Site::class));
        $manager->persist($participant);

        $this->addReference(self::PARTICIPANT_REFERENCE_PREFIX . 1, $participant);


        for ($i = 2; $i < 12; $i++) {
            $participant = new Participant();
            $participant->setNom($this->faker->lastName());
            $participant->setPrenom($this->faker->firstName());
            $participant->setTelephone($this->faker->phoneNumber());
            $participant->setMail($this->faker->email());
            $participant->setAdministrateur(false);
            $participant->setActif(true);
            $participant->isVerified(true);
            $participant->setPassword($this->userPasswordHasher->hashPassword($participant, 'fakeuser'));
            $participant->setRoles(['ROLE_USER']);

            $num_site=mt_rand(0,9);
            $participant->setSite($this->getReference(SiteFixtures::SITE_REFERENCE_PREFIX . $num_site, Site::class));

            $manager->persist($participant);

            $this->addReference(self::PARTICIPANT_REFERENCE_PREFIX . $i, $participant);
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            SiteFixtures::class,
        ];
    }
}

