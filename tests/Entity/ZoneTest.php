<?php

namespace App\Tests\Entity;

use App\Entity\Zone;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ZoneTest extends KernelTestCase
{
    private function getValidator()
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    private function validateEntity(Zone $zone): ConstraintViolationListInterface
    {
        return $this->getValidator()->validate($zone);
    }

    public function testValidZone()
    {
        $zone = (new Zone())
            ->setName('Zone Nord')
            ->setColor('#FF5733')
            ->setCoordinates([
                ["longitude" => 4.859605, "latitude" => 45.74963],
                ["longitude" => 4.857545, "latitude" => 45.742544],
            ])
            ->setTechnicien(new User());

        $errors = $this->validateEntity($zone);

        $this->assertCount(0, $errors, "La zone valide ne devrait pas générer d'erreurs.");
    }
    
    public function testValidColor()
    {
        $zone = (new Zone())
            ->setName('Zone Ouest')
            ->setColor('#A1B2C3'); // Couleur hexadécimale valide

        $errors = $this->validateEntity($zone);

        $this->assertCount(0, $errors, "Une couleur valide ne devrait pas générer d'erreurs.");
    }


    public function testInvalidColor()
    {
        $zone = (new Zone())
            ->setName('Zone Sud')
            ->setColor('#FF573'); // Mauvais format (5 caractères au lieu de 6)

        $errors = $this->validateEntity($zone);

        $this->assertGreaterThan(0, count($errors), "Une couleur invalide devrait générer une erreur.");
        $this->assertSame('La couleur doit être une suite de 6 caractère hexadécimaux.', $errors[0]->getMessage());
    }

    public function testDefaultColor()
    {
        $zone = new Zone(); // Sans setColor, donc valeur par défaut "#757575"

        $this->assertSame('#757575', $zone->getColor(), "La couleur par défaut doit être #757575.");
    }

    public function testNullColor()
    {
        $zone = (new Zone())
            ->setName('Zone Est')
            ->setColor(null);

        $errors = $this->validateEntity($zone);

        $this->assertCount(0, $errors, "Une couleur null ne devrait pas générer d'erreurs.");
    }

    public function testInvalidCoordinates()
    {
        $zone = (new Zone())->setCoordinates([
            ["longitude" => "not_a_number", "latitude" => 45.75], // Longitude invalide
            ["latitude" => 45.75], // Manque longitude
        ]);
    
        $errors = $this->validateEntity($zone);
    
        $this->assertGreaterThan(0, count($errors), "Les coordonnées invalides doivent générer une erreur.");
    }
    
    public function testValidCoordinates()
    {
        $zone = (new Zone())->setCoordinates([
            ["longitude" => 4.859605, "latitude" => 45.74963],
            ["longitude" => 4.857545, "latitude" => 45.742544],
        ]);
    
        $errors = $this->validateEntity($zone);
    
        $this->assertCount(0, $errors, "Les coordonnées valides ne doivent pas générer d'erreur.");
    }
    
    
}
