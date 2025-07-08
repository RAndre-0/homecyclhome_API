<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;

class InterventionControllerTest extends ApiTestCase
{
    public function testGetAllInterventions(): void
    {
        $this->loadFixtures();

        $token = $this->getAuthToken('admin@gmail.com', 'password');

        $this->client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $this->client->request('GET', '/api/interventions');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');
    }

    private function getAuthToken(): string
    {
        $this->client->request('POST', '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => 'admin@gmail.com',
            'password' => 'password',
        ]));

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('token', $data);

        return $data['token'];
    }
}
