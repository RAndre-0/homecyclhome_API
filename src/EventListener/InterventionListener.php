<?php

namespace App\EventListener;

use App\Entity\Intervention;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use DateInterval;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: Intervention::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Intervention::class)]
class InterventionListener
{
    public function prePersist(Intervention $intervention, PrePersistEventArgs $event): void
    {
        $this->updateEndDate($intervention);
    }

    public function preUpdate(Intervention $intervention, PreUpdateEventArgs $event): void
    {
        $this->updateEndDate($intervention);
    }

    private function updateEndDate(Intervention $intervention): void
    {
        $debut = $intervention->getDebut();
        $duree = $intervention->getTypeIntervention()->getDuree();

        if ($debut && $duree instanceof \DateTimeInterface) {
            $interval = new DateInterval('PT' . $duree->format('H') . 'H' . $duree->format('i') . 'M');
            $fin = (clone $debut)->add($interval);
            $intervention->setFin($fin);
        }
    }
}
