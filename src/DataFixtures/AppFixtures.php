<?php

namespace App\DataFixtures;

use Datetime;
use DateInterval;
use App\Entity\User;
use App\Entity\Zone;
use App\Entity\Produit;
use App\Entity\Intervention;
use App\Entity\TypeIntervention;
use App\Entity\InterventionProduit;
use App\Entity\ModeleInterventions;
use App\Entity\ModelePlanning;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $userPasswordHasher;
    public function __construct(private HttpClientInterface $client, UserPasswordHasherInterface $userPasswordHasher) {
            $this->userPasswordHasher = $userPasswordHasher;
    }
    public function load(ObjectManager $manager): void
    {
        $velosReferences = [
            "Peugeot" => ["Citystar", "RC500", "RCZ", "E-Legend"],
            "Lapierre" => ["Overvolt", "Aircode", "Xelius"],
            "Look" => ["999 Race", "795 Blade", "695", "E-765"],
            "Btwin" => ["Rockrider 540", "Ultra AF", "Riverside", "Elops"],
            "Cyfac" => ["XCR", "RSL", "Voyageur", "E-Gravel"],
            "Meral" => ["Trail 900", "Speedster", "Urban", "E-City"],
            "Dilecta" => ["Raptor", "Chrono", "Trekking", "E-Trekking"],
            "Van Rysel" => ["MTB 920", "Road Race", "Allroad", "E-Cargo"],
            "Triban" => ["Riverside 500", "RC 520", "City", "E-Cité"],
            "Gir's" => ["Enduro Pro", "Sprint", "Cruiser", "E-MTB"],
            "Victoire" => ["Summit", "Aero", "Classique", "E-Road"],
            "Origine" => ["Trail", "Endurance", "Comfort", "E-Folding"],
            "Heroïn" => ["Dirt Jump", "Fixie", "BMX", "E-BMX"],
            "Stajvelo" => ["Gravel", "Cyclocross", "Tout-terrain", "E-Adventure"]
        ];
        $firstNames = [
            'Antoine', 'Baptiste', 'Cécile', 'Dorian', 'Élise', 'Florian', 'Gabriel', 'Hélène', 'Isabelle', 'Julien',
            'Karine', 'Laurent', 'Mathilde', 'Nicolas', 'Océane', 'Pierre', 'Quentin', 'Raphaël', 'Sophie', 'Thibaut',
            'Ursule', 'Victor', 'Wilfried', 'Xavier', 'Yves', 'Zoé', 'Adrien', 'Brigitte', 'Charlotte', 'Damien',
            'Estelle', 'Fabrice', 'Géraldine', 'Hugo', 'Inès', 'Jean', 'Katell', 'Louise', 'Mélanie', 'Nathaniel',
            'Olivier', 'Pauline', 'Quentin', 'René', 'Séverine', 'Thomas', 'Ulysse', 'Valérie', 'William', 'Xénia'
        ];
        $lastNames = [
            'DUBOIS', 'MOREL', 'GARNIER', 'FAURE', 'BLANC', 'DUPONT', 'BERTRAND', 'LEMOINE', 'ROUSSEAU', 'MORIN',
            'NOËL', 'SIMON', 'LEMOINE', 'GIRARD', 'PERRIN', 'BONNET', 'DUPUIS', 'BENOIT', 'MARTEL', 'MOULIN',
            'LEFEVRE', 'CHEVALIER', 'BOUVIER', 'ROY', 'VIDAL', 'GAUTHIER', 'BARBIER', 'DENIS', 'MARCHAND', 'COLIN',
            'PERROT', 'DUMAS', 'FONTAINE', 'MALLET', 'RENARD', 'GUÉRIN', 'LEMOINE', 'MEUNIER', 'CLÉMENT', 'DUBOIS',
            'LAMBERT', 'HERVÉ', 'PICARD', 'CARPENTIER', 'PETIT', 'TISSIER', 'DURAND', 'MASSON', 'LECLERC', 'RENAUD'
        ];
        
        // Création des types d'intervention
        $typeInter1 = new TypeIntervention();
        $typeInter1->setNom("Maintenance");
        $typeInter1->setPrixDepart(30);
        $typeInter1->setDuree(new \DateTime('00:30'));
        $manager->persist($typeInter1);
        $typeInter2 = new TypeIntervention();
        $typeInter2->setNom("Réparation");
        $typeInter2->setPrixDepart(45);
        $typeInter2->setDuree(new \DateTime('00:45'));
        $manager->persist($typeInter2);
        $typesIntervention = [$typeInter1, $typeInter2];

        // Création des modèles de planning
        $modelePlanning1 = new ModelePlanning();
        $modelePlanning1->setName("modele1");
        $modelePlanning2 = new ModelePlanning();
        $modelePlanning2->setName("modele2");
        $manager->persist($modelePlanning1);
        $manager->persist($modelePlanning2);

        // Création des interventions du modèle 1
        $hour = 9;
        $minutes = 0;
        for ($i = 0 ; $i < 4 ; $i++) {
            $interventionModele = new ModeleInterventions();
            $interventionModele->setTypeIntervention($typeInter1);
            $interventionModele->setModeleIntervention($modelePlanning1);
            $interventionModele->setInterventionTime(new \DateTime("{$hour}:{$minutes}"));
            $manager->persist($interventionModele);
            $hour++;
        };
        $hour = 14;
        $minutes = 0;
        for ($i = 0 ; $i < 3 ; $i++) {
            $interventionModele = new ModeleInterventions();
            $interventionModele->setTypeIntervention($typeInter2);
            $interventionModele->setModeleIntervention($modelePlanning1);
            $interventionModele->setInterventionTime(new \DateTime("{$hour}:{$minutes}"));
            $manager->persist($interventionModele);
            $hour++;
            $minutes += 15;
        };

        // Création des interventions du modèle 2
        $hour = 9;
        $minutes = 0;
        $intervals = [0, 75, 150, 225]; // Correspond à 9:00, 10:15, 11:30, 12:45

        foreach ($intervals as $interval) {
            $interventionModele = new ModeleInterventions();
            $interventionModele->setTypeIntervention($typeInter2); // Réparation
            $interventionModele->setModeleIntervention($modelePlanning2);
            $interventionModele->setInterventionTime((new \DateTime("09:00"))->modify("+{$interval} minutes"));
            $manager->persist($interventionModele);
        }

        $hour = 14;
        $minutes = 30;
        for ($i = 0; $i < 3; $i++) {
            $interventionModele = new ModeleInterventions();
            $interventionModele->setTypeIntervention($typeInter1); // Maintenance
            $interventionModele->setModeleIntervention($modelePlanning2);
            $interventionModele->setInterventionTime(new \DateTime("{$hour}:{$minutes}"));
            $manager->persist($interventionModele);
            $hour++;
        }

        $users =  [];
        // Création des users
        for ($i = 0 ; $i < 10 ; $i++) {
            $user = new User();
            $user->setEmail("user" . $i . "@gmail.com");
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setPassword($this->userPasswordHasher->hashPassword($user, "password"));
            $users[] = $user;
            $manager->persist($user);
        }

        $technicians = [];
        // Création des techniciens
        for ($i = 0 ; $i < 8 ; $i++) {
            $userTech = new User();
            $userTech->setEmail("tech" . $i . "@gmail.com");
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $userTech->setFirstName($firstName);
            $userTech->setLastName($lastName);
            $userTech->setRoles(["ROLE_TECHNICIEN"]);
            $userTech->setPassword($this->userPasswordHasher->hashPassword($userTech, "password"));
            $technicians[] = $userTech;
            $manager->persist($userTech);
        }

        // Création d'un user admin
        $userAdmin = new User();
        $userAdmin->setEmail("admin@gmail.com");
        $firstName = $firstNames[array_rand($firstNames)];
        $lastName = $lastNames[array_rand($lastNames)];
        $userAdmin->setFirstName($firstName);
        $userAdmin->setLastName($lastName);
        $userAdmin->setRoles(["ROLE_ADMIN"]);
        $userAdmin->setPassword($this->userPasswordHasher->hashPassword($userAdmin, "password"));
        $manager->persist($userAdmin);

        // Charger le fichier JSON
        $zonesFilePath = __DIR__ . '/zones.json';
        $zonesData = json_decode(file_get_contents($zonesFilePath), true);

        // Copie du tableau des techniciens pour les zones
        $techniciansForZones = $technicians;
        foreach ($zonesData as $zoneInfo) {
            $zone = new Zone();
            $zone->setName($zoneInfo['name']);
            $zone->setColor($zoneInfo['color']);
            $zone->setCoordinates($zoneInfo['coordinates']);
        
            // Associer un technicien unique à chaque zone
            $zone->setTechnicien(array_shift($techniciansForZones));
        
            $manager->persist($zone);
        }

        /* Génération des produits */
        // Charger le fichier JSON
        $articlesFilePath = __DIR__ . '/articles.json';

        if (!file_exists($articlesFilePath)) {
            throw new \Exception("Le fichier articles.json est introuvable !");
        }

        $articlesData = json_decode(file_get_contents($articlesFilePath), true);

        $produits = [];

        foreach ($articlesData as $article) {
            $produit = new Produit();
            $produit->setDesignation($article['titre']);
            $produit->setPrix($article['prix']);
            $produit->setDescription($article['description']);
            
            $produits[] = $produit;
            $manager->persist($produit);
        }



            // Génération des interventions
            $now = new \DateTime();
            $oneYearAgo = (clone $now)->modify('-1 year');
            $oneYearLater = (clone $now)->modify('+1 year');
            for ($i = 0 ; $i < 1000 ; $i++) {
            $intervention = new Intervention();
            $intervention->setVeloElectrique($i%2);
            $intervention->setVeloCategorie("Catégorie");
            $marque = array_rand($velosReferences);
            $modeles = $velosReferences[$marque];
            $modeleAleatoire = $modeles[array_rand($modeles)];
            $intervention->setVeloMarque($marque);
            $intervention->setVeloModele($modeleAleatoire);
            $intervention->setAdresse("Adresse " . $i);
            $d = array_rand($typesIntervention);
            $intervention->setTypeIntervention($typesIntervention[random_int(0, 1)]);

            // Attribution d'un technicien
            $intervention->setTechnicien($technicians[array_rand($technicians)]);

            // Génération d'une date d'intervention aléatoire entre aujourd'hui - 1 an et aujourd'hui + 1 an
            $timestamp = random_int($oneYearAgo->getTimestamp(), $oneYearLater->getTimestamp());
            $dateIntervention = (new \DateTime())->setTimestamp($timestamp);

            // Assurer que l'heure soit entre 9h et 18h
            $hour = random_int(9, 18);
            $dateIntervention->setTime($hour, 30);
            
            $intervention->setDebut($dateIntervention);

            $pile_face = random_int(0, 1);
            if ($pile_face == 1) {
                $intervention->setClient($users[array_rand($users)]);

                $pile_face = random_int(0, 1);
                if ($pile_face == 1) {
                    $interventionProduit = new InterventionProduit();
                    $interventionProduit->setIntervention($intervention);
                    // Sélectionne un produit aléatoire
                    $produit = $produits[array_rand($produits)];
                    $interventionProduit->setProduit($produit);
                    $quantite = random_int(1, 3);
                    $interventionProduit->setQuantite($quantite);
                    $interventionProduit->setPrix($produit->getPrix() * $quantite);
                    $interventionProduit->setDesignation($produit->getDesignation());
                    $manager->persist($interventionProduit);
                }
            }
            $manager->persist($intervention);
        }

        $manager->flush();
    }
}
