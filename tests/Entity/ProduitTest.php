<?php

namespace App\Tests\Entity;

use App\Entity\Produit;
use App\Entity\InterventionProduit;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\Collection;

class ProduitTest extends TestCase
{
    public function testGetSetDesignation()
    {
        $produit = new Produit();
        $designation = "Produit Test";

        $produit->setDesignation($designation);

        $this->assertSame($designation, $produit->getDesignation());
    }

    public function testGetSetPrix()
    {
        $produit = new Produit();
        $prix = "99.99";

        $produit->setPrix($prix);

        $this->assertSame($prix, $produit->getPrix());
    }

    public function testGetSetDescription()
    {
        $produit = new Produit();
        $description = "Ceci est une description test.";

        $produit->setDescription($description);

        $this->assertSame($description, $produit->getDescription());
    }

    public function testInitializeDateValues()
    {
        $produit = new Produit();
        $produit->initializeDateValues();

        $this->assertInstanceOf(\DateTimeImmutable::class, $produit->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $produit->getModifiedAt());
    }

    public function testSetModifiedAtValue()
    {
        $produit = new Produit();
        $produit->setModifiedAtValue();

        $this->assertInstanceOf(\DateTimeInterface::class, $produit->getModifiedAt());
    }

    public function testInterventionProduitCollection()
    {
        $produit = new Produit();
        $this->assertInstanceOf(Collection::class, $produit->getInterventionProduit());
        $this->assertCount(0, $produit->getInterventionProduit());

        $interventionProduit = new InterventionProduit();
        $produit->addInterventionProduit($interventionProduit);

        $this->assertCount(1, $produit->getInterventionProduit());
        $this->assertTrue($produit->getInterventionProduit()->contains($interventionProduit));

        $produit->removeInterventionProduit($interventionProduit);
        $this->assertCount(0, $produit->getInterventionProduit());
    }
}
