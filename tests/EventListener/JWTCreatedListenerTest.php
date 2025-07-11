<?php

namespace App\Tests\EventListener;

use App\EventListener\JWTCreatedListener;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class JWTCreatedListenerTest extends TestCase
{
    public function testOnJWTCreatedAddsUserIdToPayload(): void
    {
        // Simule un utilisateur avec un ID pré déterminé
        $user = $this->createMock(\App\Entity\User::class);
        $user->method('getId')->willReturn(123);

        // Payload initial
        $initialPayload = ['email' => 'test@example.com'];

        // Mock de l’événement JWT
        $event = $this->getMockBuilder(JWTCreatedEvent::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'setData', 'getUser'])
            ->getMock();

        $event->method('getData')->willReturn($initialPayload);
        $event->method('getUser')->willReturn($user);
        $event->expects($this->once())->method('setData')->with($this->callback(function ($payload) {
            return isset($payload['id']) && $payload['id'] === 123;
        }));

        // Mock du RequestStack
        $request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($request);

        // Exécution du listener
        $listener = new JWTCreatedListener($requestStack);
        $listener->onJWTCreated($event);
    }
}
