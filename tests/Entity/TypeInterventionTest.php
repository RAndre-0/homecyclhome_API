<?php

namespace App\Tests\Entity;

use App\Entity\TypeIntervention;
use App\Entity\Intervention;
use App\Entity\ModeleInterventions;
use PHPUnit\Framework\TestCase;

class TypeInterventionTest extends TestCase
{
    public function testGetSetNom()
    {
        $typeIntervention = new TypeIntervention();
        $typeIntervention->setNom('Réparation');

        $this->assertSame('Réparation', $typeIntervention->getNom());
    }

    public function testGetSetDuree()
    {
        $typeIntervention = new TypeIntervention();
        $duree = new \DateTime('01:30:00');
        $typeIntervention->setDuree($duree);

        $this->assertSame($duree, $typeIntervention->getDuree());
    }

    public function testGetSetPrixDepart()
    {
        $typeIntervention = new TypeIntervention();
        $typeIntervention->setPrixDepart('49.99');

        $this->assertSame('49.99', $typeIntervention->getPrixDepart());
    }

    public function testAddRemoveIntervention()
    {
        $typeIntervention = new TypeIntervention();
        $intervention = new Intervention();

        $this->assertCount(0, $typeIntervention->getInterventions());

        $typeIntervention->addIntervention($intervention);
        $this->assertCount(1, $typeIntervention->getInterventions());
        $this->assertSame($typeIntervention, $intervention->getTypeIntervention());

        $typeIntervention->removeIntervention($intervention);
        $this->assertCount(0, $typeIntervention->getInterventions());
        $this->assertNull($intervention->getTypeIntervention());
    }

    public function testAddRemoveModeleIntervention()
    {
        $typeIntervention = new TypeIntervention();
        $modele = new ModeleInterventions();

        $this->assertCount(0, $typeIntervention->getModeleInterventions());

        $typeIntervention->addModeleIntervention($modele);
        $this->assertCount(1, $typeIntervention->getModeleInterventions());
        $this->assertSame($typeIntervention, $modele->getTypeIntervention());

        $typeIntervention->removeModeleIntervention($modele);
        $this->assertCount(0, $typeIntervention->getModeleInterventions());
        $this->assertNull($modele->getTypeIntervention());
    }
}
