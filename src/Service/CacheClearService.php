<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 *
 */
readonly class CacheClearService
{
    /**
     * @param TagAwareCacheInterface $cache
     */
    public function __construct(private TagAwareCacheInterface $cache) {}

    /**
     * @return void
     */
    public function clearAll(): void
    {
        $this->cache->clear();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function clearByTag(string $tag): void
    {
        $this->cache->invalidateTags([$tag]);
    }
}