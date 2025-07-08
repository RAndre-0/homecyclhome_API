<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\Intervention;
use App\Entity\CommentaireIntervention;
use App\Entity\Zone;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\ArrayCollection;

class UserTest extends TestCase
{
    public function testGetSetEmail()
    {
        $user = new User();
        $email = 'test@example.com';
        
        $user->setEmail($email);
        
        $this->assertSame($email, $user->getEmail());
    }
    
    public function testGetSetPassword()
    {
        $user = new User();
        $password = 'hashed_password';
        
        $user->setPassword($password);
        
        $this->assertSame($password, $user->getPassword());
    }
    
    public function testGetSetRoles()
    {
        $user = new User();
        $roles = ['ROLE_ADMIN'];
        
        $user->setRoles($roles);
        
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
        $this->assertContains('ROLE_USER', $user->getRoles()); // Vérifie l'ajout automatique
    }
    
    public function testGetSetFirstName()
    {
        $user = new User();
        $firstName = 'John';
        
        $user->setFirstName($firstName);
        
        $this->assertSame($firstName, $user->getFirstName());
    }
    
    public function testGetSetLastName()
    {
        $user = new User();
        $lastName = 'Doe';
        
        $user->setLastName($lastName);
        
        $this->assertSame($lastName, $user->getLastName());
    }
    
    public function testInterventionCollections()
    {
        $user = new User();
        $intervention = new Intervention();
        
        $this->assertInstanceOf(ArrayCollection::class, $user->getInterventions());
        
        $user->addIntervention($intervention);
        $this->assertCount(1, $user->getInterventions());
        $this->assertSame($user, $intervention->getTechnicien());
        
        $user->removeIntervention($intervention);
        $this->assertCount(0, $user->getInterventions());
    }
    
    public function testDemandesInterventionCollections()
    {
        $user = new User();
        $intervention = new Intervention();
        
        $this->assertInstanceOf(ArrayCollection::class, $user->getDemandesIntervention());
        
        $user->addDemandesIntervention($intervention);
        $this->assertCount(1, $user->getDemandesIntervention());
        $this->assertSame($user, $intervention->getClient());
        
        $user->removeDemandesIntervention($intervention);
        $this->assertCount(0, $user->getDemandesIntervention());
    }
    
    public function testCommentaireInterventionsCollections()
    {
        $user = new User();
        $commentaire = new CommentaireIntervention();
        
        $this->assertInstanceOf(ArrayCollection::class, $user->getCommentaireInterventions());
        
        $user->addCommentaireIntervention($commentaire);
        $this->assertCount(1, $user->getCommentaireInterventions());
        $this->assertSame($user, $commentaire->getTechnicien());
        
        $user->removeCommentaireIntervention($commentaire);
        $this->assertCount(0, $user->getCommentaireInterventions());
    }
    
    public function testZone()
    {
        $user = new User();
        $zone = new Zone();
        
        $user->setZone($zone);
        $this->assertSame($zone, $user->getZone());
        $this->assertSame($user, $zone->getTechnicien());
        
        $user->setZone(null);
        $this->assertNull($user->getZone());
        $this->assertNull($zone->getTechnicien());
    }
}
