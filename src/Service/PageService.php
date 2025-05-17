<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Page;
use App\Repository\PageRepository;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 *
 */
readonly class PageService
{

    /**
     * @param PageRepository $pageRepository
     * @param SluggerInterface $slugger
     * @param int $cacheTtl
     * @param TagAwareCacheInterface $cache
     */
    public function __construct(
        private PageRepository   $pageRepository,
        private SluggerInterface $slugger,
        private int              $cacheTtl,
        private TagAwareCacheInterface $cache)
    {

    }


    /**
     * @param string $slug
     * @return Page|null
     * @throws InvalidArgumentException
     */
    public function getPage(string $slug):?Page
    {
        return $this->cache->get('page_' . $slug, function (ItemInterface $item) use ($slug) {
            $item->tag('page');
            $item->expiresAfter($this->cacheTtl);
            return $this->pageRepository->findOneBy(['slug' => $slug]);
        });
    }

    /**
     * @return QueryBuilder
     */
    public function getPagesAll(): QueryBuilder
    {
        return $this->pageRepository->findPagesAll();
    }

    /**
     * @param Page $page
     * @return void
     */
    public function create(Page $page):void
    {
        $page->setSlug($this->generateUniqueSlug($page->getName()));
        $this->pageRepository->save($page);
    }

    /**
     * @param Page $page
     * @return void
     */
    public function delete(Page $page): void
    {
        if( $page->getSlug() !== 'home' &&
            $page->getSlug() !== 'contact' &&
            $page->getSlug() !== 'about')
        {
            $this->pageRepository->remove($page);
        }
    }

    /**
     * @param Page $page
     * @return void
     */
    public function update(Page $page): void
    {
        if( $page->getSlug() !== 'home' &&
            $page->getSlug() !== 'contact' &&
            $page->getSlug() !== 'about')
        {
            $page->setSlug($this->generateUniqueSlug($page->getName())); dump($page->getSlug());
        }

        $this->pageRepository->save($page);
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

        while ($this->pageRepository->findOneBy(['slug' => $slug])) {
            if ($i == 1) {
                $slug = $baseSlug . '-' . $i;
            }
            $i++;
        }

        return $slug;
    }
}