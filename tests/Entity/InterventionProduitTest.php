<?php

use App\Entity\InterventionProduit;
use App\Entity\Produit;
use App\Entity\Intervention;
use PHPUnit\Framework\TestCase;

class InterventionProduitTest extends TestCase
{
    public function testSetGetProduit()
    {
        $interventionProduit = new InterventionProduit();
        $produit = $this->createMock(Produit::class);
        $interventionProduit->setProduit($produit);

        // Vérification que la méthode getProduit retourne bien l'objet Produit
        $this->assertSame($produit, $interventionProduit->getProduit());
    }

    public function testSetGetIntervention()
    {
        $interventionProduit = new InterventionProduit();
        $intervention = $this->createMock(Intervention::class);
        $interventionProduit->setIntervention($intervention);

        // Vérification que la méthode getIntervention retourne bien l'objet Intervention
        $this->assertSame($intervention, $interventionProduit->getIntervention());
    }

    public function testSetGetQuantite()
    {
        $interventionProduit = new InterventionProduit();
        $interventionProduit->setQuantite(10);

        // Vérification que la méthode getQuantite retourne bien 10
        $this->assertEquals(10, $interventionProduit->getQuantite());
    }

    public function testSetGetPrix()
    {
        $interventionProduit = new InterventionProduit();
        $interventionProduit->setPrix('99.99');

        // Vérification que la méthode getPrix retourne bien '99.99'
        $this->assertEquals('99.99', $interventionProduit->getPrix());
    }

    public function testSetGetDesignation()
    {
        $interventionProduit = new InterventionProduit();
        $interventionProduit->setDesignation('Produit A');

        // Vérification que la méthode getDesignation retourne bien 'Produit A'
        $this->assertEquals('Produit A', $interventionProduit->getDesignation());
    }
}
