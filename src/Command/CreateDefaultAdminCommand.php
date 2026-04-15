<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-default-admin',
    description: 'Create default admin user from .env variables if missing.'
)]
class CreateDefaultAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        #[Autowire('%env(string:ADMIN_DEFAULT_EMAIL)%')] private readonly string $defaultAdminEmail,
        #[Autowire('%env(string:ADMIN_DEFAULT_PASSWORD)%')] private readonly string $defaultAdminPassword,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = trim($this->defaultAdminEmail);
        $password = trim($this->defaultAdminPassword);

        if ($email === '' || $password === '') {
            $io->error('ADMIN_DEFAULT_EMAIL and ADMIN_DEFAULT_PASSWORD must be configured.');

            return Command::FAILURE;
        }

        if ($this->userRepository->findOneBy(['email' => $email]) instanceof User) {
            $io->success(sprintf('Admin already exists: %s', $email));

            return Command::SUCCESS;
        }

        $admin = new User();
        $admin->setEmail($email);
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, $password));

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success(sprintf('Default admin created: %s', $email));

        return Command::SUCCESS;
    }
}
