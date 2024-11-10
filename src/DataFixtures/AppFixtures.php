<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Produit;
use App\Entity\Intervention;
use App\Entity\TypeIntervention;
use App\Entity\InterventionProduit;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private HttpClientInterface $client, UserPasswordHasherInterface $userPasswordHasher) {
            $this->userPasswordHasher = $userPasswordHasher;
    }
    public function load(ObjectManager $manager): void
    {
        $velos_references = [
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
        
        // Création des types d'intervention
        $type_inter1 = new TypeIntervention();
        $type_inter1->setNom("Maintenance");
        $type_inter1->setPrixDepart(30);
        $type_inter1->setDuree(new \DateTime('00:30'));
        $manager->persist($type_inter1);
        $type_inter2 = new TypeIntervention();
        $type_inter2->setNom("Réparation");
        $type_inter2->setPrixDepart(45);
        $type_inter2->setDuree(new \DateTime('00:45'));
        $manager->persist($type_inter2);
        $types_intervention = [$type_inter1, $type_inter2];

        // Création d'un user normal
        $user1 = new User();
        $user1->setEmail("user1@gmail.com");
        $user1->setRoles(["ROLE_USER"]);
        $user1->setPassword($this->userPasswordHasher->hashPassword($user1, "password"));
        $manager->persist($user1);
        $user2 = new User();
        $user2->setEmail("user2@gmail.com");
        $user2->setRoles(["ROLE_USER"]);
        $user2->setPassword($this->userPasswordHasher->hashPassword($user2, "password"));
        $manager->persist($user2);

        // Création d'un user technicien
        $user_tech = new User();
        $user_tech->setEmail("tech@gmail.com");
        $user_tech->setRoles(["ROLE_TECHNICIEN"]);
        $user_tech->setPassword($this->userPasswordHasher->hashPassword($user_tech, "password"));
        $manager->persist($user_tech);

        // Création d'un user admin
        $user_admin = new User();
        $user_admin->setEmail("admin@gmail.com");
        $user_admin->setRoles(["ROLE_ADMIN"]);
        $user_admin->setPassword($this->userPasswordHasher->hashPassword($user_admin, "password"));
        $manager->persist($user_admin);

        /* Génération des produits */
        for ($i = 0 ; $i < 30 ; $i++) {
            // Generate produits
            $produit = new Produit();
            $produit->setDesignation("Designation " . $i);
            $prix_produit = random_int(1, 100) - 0.01;
            $produit->setPrix($prix_produit);
            $description = $this->client->request(
                'GET',
                'https://loripsum.net/api/2/plaintext'
            );
            $produit->setDescription($description->getContent());

            // Generate interventions
            $intervention = new Intervention();
            $intervention->setVeloElectrique($i%2);
            $intervention->setVeloCategorie("Catégorie");
            $marque = array_rand($velos_references);
            $modeles = $velos_references[$marque];
            $modeleAleatoire = $modeles[array_rand($modeles)];
            $intervention->setVeloMarque($marque);
            $intervention->setVeloModele($modeleAleatoire);
            $intervention->setAdresse("Adresse " . $i);
            $d = array_rand($types_intervention);
            $intervention->setTypeIntervention($types_intervention[random_int(0, 1)]);

            // Génération InterventionProduit
            $pile_face = random_int(0, 1);
            if ($pile_face == 1) {
                $intervention_produit = new InterventionProduit();
                $intervention_produit->setIntervention($intervention);
                $intervention_produit->setProduit($produit);
                $quantite = random_int(1, 3);
                $intervention_produit->setQuantite($quantite);
                $intervention_produit->setPrix($prix_produit*$quantite);
                $manager->persist($intervention_produit);
            }

            $manager->persist($produit);
            $manager->persist($intervention);
        }

        $manager->flush();
    }
}
