<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{

    /** @param class-string<T> $entityClass The class name of the entity this repository manages */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return QueryBuilder
     */
    public function findUsersAll():QueryBuilder
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.posts', 'p')
            ->addSelect('COUNT(p.id) AS HIDDEN postCount')
            ->groupBy('u.id')
            ->orderBy('u.id', 'DESC');
    }

    /**
     * @param User $user
     * @return void
     */
    public function save(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
}
