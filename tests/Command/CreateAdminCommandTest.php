<?php

namespace App\Tests\Command;

use App\Command\CreateAdminCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateAdminCommandTest extends TestCase
{
    public function testExecuteSuccess(): void
    {
        $email = 'admin@example.com';
        $password = 'securepass';
        $firstName = 'John';
        $lastName = 'Doe';

        // Mocks
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $userRepository = $this->createMock(UserRepository::class);
        $hasher = $this->createMock(UserPasswordHasherInterface::class);

        $userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn(null);

        $hasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed-password');

        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        // Commande
        $command = new CreateAdminCommand($entityManager, $hasher, $userRepository);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('create-admin'));
        $commandTester->execute([
            'email' => $email,
            'password' => $password,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Admin user created successfully!', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testExecuteFailsWhenUserAlreadyExists(): void
    {
        $email = 'admin@example.com';

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $userRepository = $this->createMock(UserRepository::class);
        $hasher = $this->createMock(UserPasswordHasherInterface::class);

        $existingUser = new User();
        $userRepository->method('findOneBy')->with(['email' => $email])->willReturn($existingUser);

        $command = new CreateAdminCommand($entityManager, $hasher, $userRepository);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('create-admin'));
        $commandTester->execute([
            'email' => $email,
            'password' => 'password',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('existe déjà', $output);
        $this->assertSame(1, $commandTester->getStatusCode());
    }
}
