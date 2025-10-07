<?php

namespace App\DataFixtures;

use App\Entity\Ville;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class VilleFixtures extends Fixture
{
    public const VILLE_REFERENCE_PREFIX = 'ville-';
    public function __construct()
    {
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 5; $i++) {
            $ville = new Ville();
            $ville->setNom($this->faker->city());
            $ville->setCodePostal($this->faker->postcode());
            $manager->persist($ville);

            $this->addReference(self::VILLE_REFERENCE_PREFIX . $i, $ville);
        }
        $manager->flush();
    }
}
