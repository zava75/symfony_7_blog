<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 *
 */
readonly class CategoryService
{

    /**
     * @param CategoryRepository $categoryRepository
     * @param SluggerInterface $slugger
     * @param TagAwareCacheInterface $cache
     * @param int $cacheTtl
     */
    public function __construct(
        private CategoryRepository     $categoryRepository,
        private SluggerInterface       $slugger,
        private TagAwareCacheInterface $cache,
        private int                    $cacheTtl)
    {
    }

    /**
     * @return QueryBuilder
     */
    public function getCategories(): QueryBuilder
    {
        return $this->categoryRepository->findAllCategories();
    }

    /**
     * @param int $id
     * @return Category|null
     */
    public function getCategory(int $id): ?Category
    {
        return $this->categoryRepository->findOneBy(['id' => $id]);
    }


    /**
     * @param string $slug
     * @return Category|null
     * @throws InvalidArgumentException
     */
    public function getCategoryActiveBySlug(string $slug): ?Category
    {
        $cacheKey = sprintf('getCategoryActiveBySlug_%s', $slug);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($slug) {
            $item->tag('category');
            $item->expiresAfter($this->cacheTtl);
            return $this->categoryRepository->findActiveCategoryBySlug($slug);
        });
    }

    /**
     * @param Category $category
     * @return void
     */
    public function create(Category $category): void
    {
        $slug = $this->generateUniqueSlug($category->getName());
        $category->setSlug($slug);

        $this->categoryRepository->save($category);
    }

    /**
     * @param Category $category
     * @return void
     */
    public function update(Category $category): void
    {
        $slug = $this->generateUniqueSlug($category->getName());
        $category->setSlug($slug);

        $this->categoryRepository->save($category);
    }

    /**
     * @param Category $category
     * @return void
     */
    public function delete(Category $category): void
    {
        $this->categoryRepository->remove($category);
    }

    /**
     * @param string $name
     * @return string
     */
    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = strtolower((string)$this->slugger->slug($name));
        $slug = $baseSlug;
        $i = 1;

        while ($this->categoryRepository->findOneBy(['slug' => $slug])) {
            if ($i == 1) {
                $slug = $baseSlug . '-' . $i;
            }
            $i++;
        }

        return $slug;
    }

    /**
     * @return array
     * @throws InvalidArgumentException
     */
    public function findAllWithChildren(): array
    {
        return $this->cache->get('findAllWithChildren', function (ItemInterface $item) {
            $item->tag('category');
            $item->expiresAfter($this->cacheTtl);
            return $this->categoryRepository->findAllWithChildren();
        });
    }

    /**
     * @return array
     */
    public function getAllWithPostCounts(): array
    {
        $flat = $this->categoryRepository->findAllActiveWithPostCount();

        $structured = [];

        foreach ($flat as $row) {
            $parentId = $row['parent_id'];
            if (!isset($structured[$parentId])) {
                $structured[$parentId] = [
                    'id' => $parentId,
                    'name' => $row['parent_name'],
                    'slug' => $row['parent_slug'],
                    'children' => [],
                ];
            }

            if ($row['child_id']) {
                $structured[$parentId]['children'][] = [
                    'id' => $row['child_id'],
                    'name' => $row['child_name'],
                    'slug' => $row['child_slug'],
                    'post_count' => (int) $row['post_count'],
                ];
            }
        }

        return array_values($structured);
    }

}