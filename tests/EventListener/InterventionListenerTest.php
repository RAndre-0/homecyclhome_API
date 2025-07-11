<?php

namespace App\Tests\EventListener;

use App\Entity\Intervention;
use App\Entity\TypeIntervention;
use App\EventListener\InterventionListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;

class InterventionListenerTest extends TestCase
{
    public function testPrePersistSetsEndDate(): void
    {
        $intervention = new Intervention();
        $type = new TypeIntervention();

        // Durée au format DateTime représentant une durée (ex : 01:30:00)
        $duree = new \DateTimeImmutable('1970-01-01 01:30:00');
        $type->setDuree($duree);

        $debut = new \DateTimeImmutable('2025-07-11 09:00:00');
        $intervention->setDebut($debut);
        $intervention->setTypeIntervention($type);

        // Création d'un PrePersistEventArgs valide (avec entity et ObjectManager fictif)
        $event = new PrePersistEventArgs($intervention, $this->createMock(ObjectManager::class));

        $listener = new InterventionListener();
        $listener->prePersist($intervention, $event);

        // Le listener ajoute directement les heures/minutes de la durée
        $expectedFin = new \DateTimeImmutable('2025-07-11 10:30:00');
        $this->assertEquals($expectedFin, $intervention->getFin());
    }

    public function testPreUpdateAlsoSetsEndDate(): void
    {
        $intervention = new Intervention();
        $type = new TypeIntervention();

        $duree = new \DateTimeImmutable('1970-01-01 00:45:00');
        $type->setDuree($duree);

        $debut = new \DateTimeImmutable('2025-07-11 08:00:00');
        $intervention->setDebut($debut);
        $intervention->setTypeIntervention($type);

        // Utiliser une référence pour le changeSet et EntityManagerInterface
        $changeSet = [];
        $event = new PreUpdateEventArgs($intervention, $this->createMock(EntityManagerInterface::class), $changeSet);

        $listener = new InterventionListener();
        $listener->preUpdate($intervention, $event);

        // Le listener ajoute directement les heures/minutes de la durée
        $expectedFin = new \DateTimeImmutable('2025-07-11 08:45:00');
        $this->assertEquals($expectedFin, $intervention->getFin());
    }
}