<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidCoordinatesValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidCoordinates) {
            throw new UnexpectedTypeException($constraint, ValidCoordinates::class);
        }

        if (null === $value || !is_array($value)) {
            return;
        }

        foreach ($value as $point) {
            if (!is_array($point) || !isset($point['longitude'], $point['latitude'])) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();
                return;
            }

            if (!is_numeric($point['longitude']) || !is_numeric($point['latitude'])) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();
                return;
            }
        }
    }
}