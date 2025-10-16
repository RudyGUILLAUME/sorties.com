<?php

namespace App\Service;

use App\Entity\Participant;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class FichierCSVService
{
    public function __construct(private readonly SiteRepository $siteRepository)
    {
    }

    public function createUsers(String $pathname,EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher):array
    {
        $handle = fopen($pathname, 'r');
        $header = fgetcsv($handle, 1000, ',');

        $count = 0;
        $errors = [];
        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            [$nom, $prenom, $telephone,$mail,  $site, $administrateur,$actif,$password] = $data;

            $existingUser = $em->getRepository(Participant::class)->findOneBy(['mail' => $mail]);
            if ($existingUser) {
                $errors[] = "❌ L'adresse mail {$mail} existe déjà.";
                continue;
            }

            $participant = new Participant();
            $participant->setNom($nom);
            $participant->setPrenom($prenom);
            $participant->setTelephone($telephone);
            $participant->setMail($mail);
            $val=$this->siteRepository->findByName($site);
            if($val!=null)
            {
                $participant->setSite($val);
            }
            $participant->setAdministrateur(filter_var($administrateur, FILTER_VALIDATE_BOOLEAN));
            $participant->setActif(filter_var($actif, FILTER_VALIDATE_BOOLEAN));
            $participant->setRoles(['ROLE_USER']);
            if($participant->isAdministrateur())
            {
                $participant->addRoles('ROLE_ADMIN');
            }

            $hashedPassword = $passwordHasher->hashPassword($participant, $password);
            $participant->setPassword($hashedPassword);

            $em->persist($participant);
            $count++;
        }

        fclose($handle);
        $em->flush();

        return [$count,$errors];

    }
}