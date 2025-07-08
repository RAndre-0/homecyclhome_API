<?php

namespace App\Tests\Entity;

use App\Entity\CommentaireIntervention;
use App\Entity\Intervention;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class CommentaireInterventionTest extends TestCase
{
    public function testGetSetContenu()
    {
        $commentaire = new CommentaireIntervention();
        $contenu = "Ceci est un commentaire de test.";

        $commentaire->setContenu($contenu);

        $this->assertSame($contenu, $commentaire->getContenu());
    }

    public function testGetSetCreatedAt()
    {
        $commentaire = new CommentaireIntervention();
        $date = new \DateTimeImmutable('2024-01-01 12:00:00');

        $commentaire->setCreatedAt($date);

        $this->assertSame($date, $commentaire->getCreatedAt());
    }

    public function testGetSetTechnicien()
    {
        $commentaire = new CommentaireIntervention();
        $technicien = new User();

        $commentaire->setTechnicien($technicien);

        $this->assertSame($technicien, $commentaire->getTechnicien());
    }

    public function testGetSetIntervention()
    {
        $commentaire = new CommentaireIntervention();
        $intervention = new Intervention();

        $commentaire->setIntervention($intervention);

        $this->assertSame($intervention, $commentaire->getIntervention());
    }
}
