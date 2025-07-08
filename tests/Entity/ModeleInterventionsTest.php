<?php

namespace App\Tests\Entity;

use App\Entity\ModeleInterventions;
use App\Entity\ModelePlanning;
use App\Entity\TypeIntervention;
use PHPUnit\Framework\TestCase;

class ModeleInterventionsTest extends TestCase
{
    public function testGetSetInterventionTime()
    {
        $modeleIntervention = new ModeleInterventions();
        $time = new \DateTime('10:30:00');

        $modeleIntervention->setInterventionTime($time);

        $this->assertSame($time, $modeleIntervention->getInterventionTime());
    }

    public function testGetSetTypeIntervention()
    {
        $modeleIntervention = new ModeleInterventions();
        $typeIntervention = new TypeIntervention();
        
        $modeleIntervention->setTypeIntervention($typeIntervention);

        $this->assertSame($typeIntervention, $modeleIntervention->getTypeIntervention());
    }

    public function testGetSetModeleIntervention()
    {
        $modeleIntervention = new ModeleInterventions();
        $modelePlanning = new ModelePlanning();

        $modeleIntervention->setModeleIntervention($modelePlanning);

        $this->assertSame($modelePlanning, $modeleIntervention->getModeleIntervention());
    }
}
