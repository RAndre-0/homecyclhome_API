<?php

namespace App\Tests\Entity;

use App\Entity\Marque;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use PHPUnit\Framework\TestCase;

class MarqueTest extends TestCase
{
    private function getValidator()
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    public function testGetSetNom()
    {
        $marque = new Marque();
        $marque->setNom('Trek');

        $this->assertSame('Trek', $marque->getNom());
    }

    public function testGetSetLogo()
    {
        $marque = new Marque();
        $marque->setLogo('logo.png');

        $this->assertSame('logo.png', $marque->getLogo());
    }

    public function testGetSetCouleur()
    {
        $marque = new Marque();
        $marque->setCouleur('#FF5733');

        $this->assertSame('#FF5733', $marque->getCouleur());
    }

    public function testGetSetCouleurNull()
    {
        $marque = new Marque();
        $marque->setCouleur(null);

        $this->assertNull($marque->getCouleur());
    }


    public function testValidCouleur()
    {
        $validator = $this->getValidator();
        $marque = new Marque();
        $marque->setCouleur('#A1B2C3');

        $errors = $validator->validate($marque);

        $this->assertCount(0, $errors, "Aucune erreur ne doit être retournée pour une couleur valide.");
    }

    public function testInvalidCouleur()
    {
        $validator = $this->getValidator();
        $marque = new Marque();
        $marque->setCouleur('#XYZ123');

        /** @var ConstraintViolationListInterface $errors */
        $errors = $validator->validate($marque);

        $this->assertGreaterThan(0, count($errors), "Une erreur doit être retournée pour une couleur invalide.");
        $this->assertSame('La couleur doit être une suite de 6 caractère hexadécimaux.', $errors[0]->getMessage());
    }
}
