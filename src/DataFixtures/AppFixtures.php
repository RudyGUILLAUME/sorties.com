<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $manager->flush();
    }

    // Manage Ordre d'execution des fixtures BP
    public function getDependencies()
    {
        return [
            EtatFixtures::class,
            VilleFixtures::class,
            LieuFixtures::class,
            SiteFixtures::class,
            ParticipantFixtures::class,
            SortieFixtures::class,
        ];
    }
}
