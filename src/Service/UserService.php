<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 *
 */
readonly class UserService
{
    /**
     * @param UserRepository $userRepository
     * @param UserPasswordHasherInterface $userPasswordHasher
     */
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $userPasswordHasher)
    {
    }

    /**
     * @return array
     */
    public function getUsers(): array
    {
        return $this->userRepository->findAll();
    }

    /**
     * @param int $id
     * @return User|null
     */
    public function getUser(int $id): ?User
    {
        return $this->userRepository->findOneBy(['id' => $id]);
    }

    /**
     * @return QueryBuilder
     */
    public function getUsersAll():QueryBuilder
    {
        return $this->userRepository->findUsersAll();
    }

    /**
     * @param User $user
     * @param string $plainPassword
     * @return void
     */
    public function save(User $user, string $plainPassword): void
    {
        $user->setPassword($this->userPasswordHasher->hashPassword($user, $plainPassword));
        $user->setRoles(['ROLE_USER']);

        $this->userRepository->save($user);
    }
}