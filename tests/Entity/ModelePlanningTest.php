<?php

namespace App\Tests\Entity;

use App\Entity\ModelePlanning;
use App\Entity\ModeleInterventions;
use PHPUnit\Framework\TestCase;

class ModelePlanningTest extends TestCase
{
    public function testGetSetName()
    {
        $modelePlanning = new ModelePlanning();
        $modelePlanning->setName('Planning Standard');

        $this->assertSame('Planning Standard', $modelePlanning->getName());
    }

    public function testAddRemoveModeleIntervention()
    {
        $modelePlanning = new ModelePlanning();
        $modeleIntervention = new ModeleInterventions();
    
        $modelePlanning->addModeleIntervention($modeleIntervention);
        $this->assertSame($modelePlanning, $modeleIntervention->getModeleIntervention());
    
        $modelePlanning->removeModeleIntervention($modeleIntervention);
        $this->assertNull($modeleIntervention->getModeleIntervention());
    }    
}
