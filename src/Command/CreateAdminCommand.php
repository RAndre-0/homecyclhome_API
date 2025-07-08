<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'create-admin',
    description: 'Create a new admin user',
)]
class CreateAdminCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $hasher;
    private UserRepository $userRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $hasher,
        UserRepository $userRepository
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->hasher = $hasher;
        $this->userRepository = $userRepository;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email of the admin')
            ->addArgument('password', InputArgument::REQUIRED, 'Password of the admin')
            ->addArgument('first_name', InputArgument::REQUIRED, 'First name of the admin')
            ->addArgument('last_name', InputArgument::REQUIRED, 'Last name of the admin');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);

        foreach (['email', 'password', 'first_name', 'last_name'] as $arg) {
            if (!$input->getArgument($arg)) {
                $value = $arg === 'password'
                    ? $io->askHidden("Please enter the $arg of the admin")
                    : $io->ask("Please enter the $arg of the admin");

                $input->setArgument($arg, $value);
            }
        }
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $io = new SymfonyStyle($input, $output);
        $io->title('Create Admin Command');
        $io->section('Initializing...');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        if ($this->userRepository->findOneBy(['email' => $email])) {
            $io->error("Un utilisateur avec l'email \"$email\" existe déjà.");
            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($input->getArgument('first_name'));
        $user->setLastName($input->getArgument('last_name'));
        $user->setPassword(
            $this->hasher->hashPassword(
                $user,
                $input->getArgument('password')
            )
        );
        $user->setRoles(['ROLE_ADMIN']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Admin user created successfully!');

        return Command::SUCCESS;
    }
}

