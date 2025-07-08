<?php

namespace App\Tests\Entity;

use App\Entity\Intervention;
use App\Entity\User;
use App\Entity\InterventionProduit;
use App\Entity\CommentaireIntervention;
use PHPUnit\Framework\TestCase;

class InterventionTest extends TestCase
{
    public function testGetSetVeloCategorie()
    {
        $intervention = new Intervention();
        $intervention->setVeloCategorie('VTT');

        $this->assertSame('VTT', $intervention->getVeloCategorie());
    }

    public function testGetSetVeloElectrique()
    {
        $intervention = new Intervention();
        $intervention->setVeloElectrique(true);

        $this->assertTrue($intervention->isVeloElectrique());
    }

    public function testGetSetVeloMarque()
    {
        $intervention = new Intervention();
        $intervention->setVeloMarque('Specialized');

        $this->assertSame('Specialized', $intervention->getVeloMarque());
    }

    public function testGetSetAdresse()
    {
        $intervention = new Intervention();
        $intervention->setAdresse('123 Rue du Vélo');

        $this->assertSame('123 Rue du Vélo', $intervention->getAdresse());
    }

    public function testGetSetCommentaireClient()
    {
        $intervention = new Intervention();
        $intervention->setCommentaireClient('Réparation urgente');

        $this->assertSame('Réparation urgente', $intervention->getCommentaireClient());
    }

    public function testGetSetPhoto()
    {
        $intervention = new Intervention();
        $intervention->setPhoto('image.jpg');

        $this->assertSame('image.jpg', $intervention->getPhoto());
    }

    public function testGetSetClient()
    {
        $intervention = new Intervention();
        $client = new User();
        $intervention->setClient($client);

        $this->assertSame($client, $intervention->getClient());
    }

    public function testGetSetTechnicien()
    {
        $intervention = new Intervention();
        $technicien = new User();
        $intervention->setTechnicien($technicien);

        $this->assertSame($technicien, $intervention->getTechnicien());
    }

    public function testAddRemoveInterventionProduit()
    {
        $intervention = new Intervention();
        $produit = new InterventionProduit();

        $this->assertCount(0, $intervention->getInterventionProduit());

        $intervention->addInterventionProduit($produit);
        $this->assertCount(1, $intervention->getInterventionProduit());
        $this->assertSame($intervention, $produit->getIntervention());

        $intervention->removeInterventionProduit($produit);
        $this->assertCount(0, $intervention->getInterventionProduit());
        $this->assertNull($produit->getIntervention());
    }

    public function testAddRemoveCommentaire()
    {
        $intervention = new Intervention();
        $commentaire = new CommentaireIntervention();

        $this->assertCount(0, $intervention->getCommentaires());

        $intervention->addCommentaire($commentaire);
        $this->assertCount(1, $intervention->getCommentaires());
        $this->assertSame($intervention, $commentaire->getIntervention());

        $intervention->removeCommentaire($commentaire);
        $this->assertCount(0, $intervention->getCommentaires());
        $this->assertNull($commentaire->getIntervention());
    }

    public function testGetSetDebut()
    {
        $intervention = new Intervention();
        $date = new \DateTime();
        $intervention->setDebut($date);

        $this->assertSame($date, $intervention->getDebut());
    }
}
