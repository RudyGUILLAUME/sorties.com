<?php

namespace App\DataFixtures;

use App\Entity\Site;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class SiteFixtures extends Fixture
{
    public const SITE_REFERENCE_PREFIX = 'site-';
    public function __construct()
    {
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 10; $i++) {
            $site = new Site();
            $site->setNom($this->faker->company());
            $manager->persist($site);

            $this->addReference(self::SITE_REFERENCE_PREFIX . $i, $site);
        }
        $manager->flush();
    }
}
