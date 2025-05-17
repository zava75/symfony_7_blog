<?php

namespace App\Repository;

use AllowDynamicProperties;
use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
#[AllowDynamicProperties] class CategoryRepository extends ServiceEntityRepository
{
    /** @param class-string<T> $entityClass The class name of the entity this repository manages */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
        $this->connection = $registry->getConnection();
    }

    /**
     * @return array
     */
    public function findWithParent(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.parent IS NOT NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array
     */
    public function findAllWithChildren(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.children', 'child')
            ->addSelect('child')
            ->where('c.isActive = true')
            ->andWhere('c.parent IS NULL')
            ->andWhere('child.isActive = true')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array
     */
    public function findAllActiveWithPostCount(): array
    {
        $sql = <<<SQL
        SELECT 
            parent.id AS parent_id,
            parent.name AS parent_name,
            parent.slug AS parent_slug,
            child.id AS child_id,
            child.name AS child_name,
            child.slug AS child_slug,
            COUNT(p.id) AS post_count
        FROM category parent
        LEFT JOIN category child ON child.parent_id = parent.id AND child.is_active = true
        LEFT JOIN post p ON p.category_id = child.id AND p.is_active = true
        WHERE parent.is_active = true AND parent.parent_id IS NULL
        GROUP BY parent.id, parent.name, parent.slug, child.id, child.name, child.slug
        ORDER BY parent.name, child.name
    SQL;

        return $this->connection->executeQuery($sql)->fetchAllAssociative();
    }

    /**
     * @param string $slug
     * @return Category|null
     */
    public function findActiveCategoryBySlug(string $slug): ?Category
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.parent', 'p')
            ->addSelect('p')
            ->leftJoin('c.children', 'ch')
            ->addSelect('ch')
            ->where('c.slug = :slug')
            ->andWhere('c.isActive = true')
            ->andWhere('p IS NULL OR p.isActive = true')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return QueryBuilder
     */
    public function createActiveWithActiveParentQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.parent', 'p')
            ->where('c.isActive = :active')
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.name', 'ASC');
    }

    /**
     * @return QueryBuilder
     */
    public function findAllActiveRootCategories(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->where('c.isActive = :active')
            ->andWhere('c.parent IS NULL')
            ->setParameter('active', true)
            ->orderBy('c.name', 'ASC');
    }

    /**
     * @param Category $category
     * @return void
     */
    public function save(Category $category): void
    {
        $this->getEntityManager()->persist($category);
        $this->getEntityManager()->flush();
    }

    /**
     * @return QueryBuilder
     */
    public function findAllCategories(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.id', 'DESC');
    }

    /**
     * @param Category $category
     * @return void
     */
    public function remove(Category $category): void
    {
        $this->getEntityManager()->remove($category);
        $this->getEntityManager()->flush();
    }

}
