<?php

namespace App\DataFixtures;

use App\Entity\Lieu;
use App\Entity\Ville;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class LieuFixtures extends Fixture implements DependentFixtureInterface
{
    public const LIEU_REFERENCE_PREFIX = 'lieu-';
    public function __construct()
    {
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 10; $i++) {
            $lieu = new Lieu();
            $lieu->setNom($this->faker->words(3,true));
            $lieu->setRue($this->faker->streetAddress());
            $lieu->setLatitude($this->faker->latitude());
            $lieu->setLongitude($this->faker->longitude());
            $num_ville=mt_rand(0,4);
            $lieu->setVille($this->getReference(VilleFixtures::VILLE_REFERENCE_PREFIX . $num_ville, Ville::class));
            $manager->persist($lieu);

            $this->addReference(self::LIEU_REFERENCE_PREFIX . $i, $lieu);
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            VilleFixtures::class,
        ];
    }
}
