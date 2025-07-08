<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\HttpFoundation\RequestStack;

class JWTCreatedListener
{
    private $requestStack;

    /**
     * Constructeur pour injecter le service RequestStack.
     *
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Modifie le payload du JWT lors de sa création.
     *
     * @param JWTCreatedEvent $event
     *
     * @return void
     */
    public function onJWTCreated(JWTCreatedEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();

        // Ajout des données personnalisées au payload
        $payload = $event->getData();
        $payload['id'] = $event->getUser()->getId(); // Ajouter l'ID de l'utilisateur
        $event->setData($payload);
    }
}
