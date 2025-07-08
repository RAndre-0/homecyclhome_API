<?php

namespace App\Service;

use App\Entity\Intervention;
use App\Entity\ModelePlanning;
use App\Entity\User;

class InterventionBatchGenerator
{
    /**
     * Génère une liste d'interventions à partir d'un modèle, de techniciens et d'une plage de dates
     */
    public function generateInterventions(
        ModelePlanning $modele,
        array $techniciens,
        \DatePeriod $dates
    ): array {
        $interventions = [];

        foreach ($dates as $date) {
            foreach ($techniciens as $technicien) {
                foreach ($modele->getModeleInterventions() as $modeleIntervention) {
                    $intervention = new Intervention();
                    $intervention->setDebut((clone $date)->setTime(
                        (int)$modeleIntervention->getInterventionTime()->format('H'),
                        (int)$modeleIntervention->getInterventionTime()->format('i'),
                        0
                    ));
                    $intervention->setTypeIntervention($modeleIntervention->getTypeIntervention());
                    $intervention->setTechnicien($technicien);

                    $interventions[] = $intervention;
                }
            }
        }

        return $interventions;
    }
}
