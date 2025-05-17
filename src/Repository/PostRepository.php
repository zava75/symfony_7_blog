<?php

namespace App\Repository;

use AllowDynamicProperties;
use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends ServiceEntityRepository<Post>
 */
#[AllowDynamicProperties] class PostRepository extends ServiceEntityRepository
{
    /** @param class-string<T> $entityClass The class name of the entity this repository manages */
    public function __construct(ManagerRegistry $registry, private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, Post::class);
        $this->connection = $registry->getConnection();
    }

    /**
     * @param int $limit
     * @return array
     */
    public function findLatestPosts(int $limit): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->addSelect('c')
            ->leftJoin('c.parent', 'parentCategory')
            ->addSelect('parentCategory')
            ->where('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.id', 'DESC')
            ->addOrderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return QueryBuilder
     */
    public function createLatestPostsQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->addSelect('c')
            ->leftJoin('c.parent', 'parentCategory')
            ->addSelect('parentCategory')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->where('p.isActive = :active')
            ->andWhere('c.isActive = :active')
            ->andWhere('parentCategory.isActive = :active OR parentCategory IS NULL')
            ->setParameter('active', true)
            ->orderBy('p.id', 'DESC')
            ->addOrderBy('p.createdAt', 'DESC');
    }

    /**
     * @param array $categoryIds
     * @return QueryBuilder
     */
    public function findPostsByCategoryIds(array $categoryIds): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->addSelect('c')
            ->leftJoin('c.parent', 'parentCategory')
            ->addSelect('parentCategory')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->where('p.isActive = :active')
            ->andWhere('c.id IN (:categoryIds)')
            ->andWhere('c.isActive = :active')
            ->andWhere('parentCategory.isActive = :active OR parentCategory IS NULL')
            ->setParameter('active', true)
            ->setParameter('categoryIds', $categoryIds)
            ->orderBy('p.createdAt', 'DESC');
    }

    /**
     * @param int $userId
     * @return QueryBuilder
     */
    public function findPostsUser(int $userId): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->join('p.user', 'u')
            ->where('u.id = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('p.createdAt', 'DESC');
    }

    /**
     * @return QueryBuilder
     */
    public function findPostsAll(): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC');
    }

    /**
     * @param string $slug
     * @return Post|null
     */
    public function findOneIsActive(string $slug):?Post
    {
        return $this->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->leftJoin('c.parent', 'pc')
            ->addSelect('c', 'pc')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->where('p.slug = :slug')
            ->andWhere('p.isActive = true')
            ->andWhere('c.isActive = true')
            ->andWhere('pc IS NULL OR pc.isActive = true')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Post $post
     * @return void
     */
    public function save(Post $post): void
    {
        $this->getEntityManager()->persist($post);
        $this->getEntityManager()->flush();
    }

    /**
     * @param Post $post
     * @return void
     */
    public function remove(Post $post): void
    {
        $this->getEntityManager()->remove($post);
        $this->getEntityManager()->flush();
    }

    /**
     * @param string $search
     * @return QueryBuilder
     */
    public function searchPosts(string $search): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.category', 'c')
            ->innerJoin('c.parent', 'parentCategory')
            ->addSelect('c', 'parentCategory')
            ->where('p.isActive = true')
            ->andWhere('c.isActive = true')
            ->andWhere('parentCategory.isActive = true')
            ->andWhere('p.title LIKE :search OR p.name LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('p.createdAt', 'DESC');
    }


    /**
     * @param int $categoryId
     * @param int $excludePostId
     * @param int $limit
     * @return array
     */
    public function findActivePostsByCategoryLimit(int $categoryId, int $excludePostId, int $limit = 3): array
    {
        $sql = <<<SQL
    SELECT p.*, c.name as category_name, pc.name as parent_category_name,
           c.slug as category_slug, pc.slug as parent_category_slug, u.name as user_name
    FROM post p
    INNER JOIN "user" u ON p.user_id = u.id
    INNER JOIN category c ON p.category_id = c.id
    LEFT JOIN category pc ON c.parent_id = pc.id
    WHERE p.is_active = true
      AND c.is_active = true
      AND (pc.id IS NULL OR pc.is_active = true)
      AND c.id = :categoryId
      AND p.id != :excludePostId
    ORDER BY RANDOM()
    LIMIT :limit
SQL;

        $stmt = $this->connection->prepare($sql);

        $stmt->bindValue('categoryId', $categoryId);
        $stmt->bindValue('excludePostId', $excludePostId);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);

        $result = $stmt->executeQuery();

        return $result->fetchAllAssociative();
    }

    /**
     * @param int $id
     * @return Post
     * @throws ORMException
     */
    public function getReferenceById(int $id): Post
    {
        return $this->entityManager->getReference(Post::class, $id);
    }
}
