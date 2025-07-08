<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient(); // Boot ici, une seule fois
    }

    protected function loadFixtures(): void
    {
        $container = static::getContainer(); // Pas besoin de bootKernel()
        $em = $container->get(EntityManagerInterface::class);

        $purger = new ORMPurger($em);
        $executor = new ORMExecutor($em, $purger);
        $executor->purge();

        $fixtures = $container->get(\App\DataFixtures\AppFixtures::class);
        $executor->execute([$fixtures]);
    }
}
