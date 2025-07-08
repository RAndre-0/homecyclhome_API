<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Entity\Intervention;
use App\Entity\TypeIntervention;
use App\Entity\ModelePlanning;
use App\Entity\ModeleInterventions;
use App\Service\InterventionBatchGenerator;
use PHPUnit\Framework\TestCase;

class InterventionBatchGeneratorTest extends TestCase
{
    public function testGenerateInterventionsReturnsExpectedInstances()
    {
        // Préparation du modèle de planning et des modèleIntervention associés
        $type = new TypeIntervention();
        $type->setNom("Révision");

        $heure = new \DateTimeImmutable('09:00');
        $modeleIntervention = new ModeleInterventions();
        $modeleIntervention->setTypeIntervention($type);
        $modeleIntervention->setInterventionTime($heure);

        $modelePlanning = new ModelePlanning();
        $modelePlanning->addModeleIntervention($modeleIntervention);

        // Préparation d’un technicien fictif
        $technicien = new User();
        $techniciens = [$technicien];

        // Définition d'une plage de 3 jours
        $from = new \DateTime('2024-01-01');
        $to = new \DateTime('2024-01-03');
        $interval = new \DateInterval('P1D');
        $dateRange = new \DatePeriod($from, $interval, (clone $to)->modify('+1 day'));

        // Appel du service
        $generator = new InterventionBatchGenerator();
        $interventions = $generator->generateInterventions($modelePlanning, $techniciens, $dateRange);

        // Vérifications
        $this->assertCount(3, $interventions, "On attend une intervention par jour pour 1 technicien.");

        foreach ($interventions as $i => $intervention) {
            $this->assertInstanceOf(Intervention::class, $intervention);
            $this->assertSame($technicien, $intervention->getTechnicien());
            $this->assertSame($type, $intervention->getTypeIntervention());

            $expectedDate = (clone $from)->modify("+$i days")->setTime(9, 0);
            $this->assertEquals($expectedDate, $intervention->getDebut(), "Date d'intervention incorrecte");
        }
    }
}
