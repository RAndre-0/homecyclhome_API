<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends WebTestCase
{
    private function createAuthenticatedClient(): array
    {
        $client = static::createClient();
        $container = static::getContainer();

        $entityManager = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('test-auth-' . uniqid() . '@example.com');
        $user->setFirstName('Test');
        $user->setLastName('Auth');
        $user->setPassword('irrelevant');
        $user->setRoles(['ROLE_ADMIN']);

        $entityManager->persist($user);
        $entityManager->flush();

        $jwtManager = $container->get('lexik_jwt_authentication.jwt_manager');
        $token = $jwtManager->create($user);

        return [$client, $token];
    }

    public function testGetAllUsers(): void
    {
        [$client, $token] = $this->createAuthenticatedClient();

        $client->request('GET', '/api/users', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $token
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');
    }

    public function testGetUsersByRole(): void
    {
        [$client, $token] = $this->createAuthenticatedClient();

        $client->request('GET', '/api/users/role-ROLE_ADMIN', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $token
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');
    }

    public function testGetUserById(): void
    {
        [$client, $token] = $this->createAuthenticatedClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('test-auth-' . uniqid() . '@example.com');
        $user->setFirstName('User');
        $user->setLastName('Two');
        $user->setPassword('irrelevant');
        $user->setRoles(['ROLE_USER']);
        $entityManager->persist($user);
        $entityManager->flush();

        $client->request('GET', '/api/users/' . $user->getId(), [], [], [
            'HTTP_Authorization' => 'Bearer ' . $token
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');
    }

    public function testGetUserNotFound(): void
    {
        [$client, $token] = $this->createAuthenticatedClient();

        $client->request('GET', '/api/users/9999999', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $token
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
