<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

#[\Attribute]
class ValidCoordinates extends Constraint
{
    public string $message = "Chaque point doit contenir des valeurs valides de longitude et latitude.";

    // all configurable options must be passed to the constructor
    public function __construct(?string $message = null)
    {
        parent::__construct([]);
        $this->message = $message ?? $this->message;
    }
}