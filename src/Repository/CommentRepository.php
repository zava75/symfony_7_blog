<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    /** @param class-string<T> $entityClass The class name of the entity this repository manages */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * @param int|null $postId
     * @return array
     */
    public function findActiveCommentsByPost(int $postId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.post = :postId')
            ->andWhere('c.isActive = true')
            ->setParameter('postId', $postId)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return QueryBuilder
     */
    public function findCommentsAll():QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->join('c.post', 'p')
            ->join('p.category', 'sc')
            ->leftJoin('sc.parent', 'pc')
            ->addSelect('p', 'sc', 'pc')
            ->orderBy('c.createdAt', 'DESC');
    }

    /**
     * @return QueryBuilder
     */
    public function findCommentsAllNoActive():QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->where('c.isActive = false')
            ->join('c.post', 'p')
            ->join('p.category', 'sc')
            ->leftJoin('sc.parent', 'pc')
            ->addSelect('p', 'sc', 'pc')
            ->orderBy('c.createdAt', 'DESC');
    }


    /**
     * @param Comment $comment
     * @return void
     */
    public function remove(Comment $comment): void
    {
        $this->getEntityManager()->remove($comment);
        $this->getEntityManager()->flush();
    }

    /**
     * @param Comment $comment
     * @return void
     */
    public function setActivate(Comment $comment): void
    {
        $comment->setIsActive(true);
        $this->getEntityManager()->persist($comment);
        $this->getEntityManager()->flush();
    }

    /**
     * @param Comment $comment
     * @return void
     */
    public function setDeactivate(Comment $comment): void
    {
        $comment->setIsActive(false);
        $this->getEntityManager()->persist($comment);
        $this->getEntityManager()->flush();
    }

    /**
     * @param Comment $comment
     * @return void
     */
    public function save(Comment $comment): void
    {
        $this->getEntityManager()->persist($comment);
        $this->getEntityManager()->flush();
    }
}
