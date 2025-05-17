<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 *
 */
readonly class CommentService {

    /**
     * @param CommentRepository $commentRepository
     * @param int $cacheTtl
     * @param TagAwareCacheInterface $cache
     */
    public function __construct(
        private CommentRepository      $commentRepository,
        private int                    $cacheTtl,
        private TagAwareCacheInterface $cache)
    {
    }

    /**
     * @param int|null $postId
     * @return array
     * @throws InvalidArgumentException
     */
    public function getCommentsByPost(?int $postId): array
    {
        return $this->cache->get('getCommentsByPost' . $postId , function (ItemInterface $item) use ($postId) {
            $item->tag('category');
            $item->expiresAfter($this->cacheTtl);
            return $this->commentRepository->findActiveCommentsByPost($postId);
        });
    }

    /**
     * @return QueryBuilder
     */
    public function getCommentsAll():QueryBuilder
    {
        return $this->commentRepository->findCommentsAll();
    }

    /**
     * @return QueryBuilder
     */
    public function getCommentsNotActive():QueryBuilder
    {
        return $this->commentRepository->findCommentsAllNoActive();
    }

    /**
     * @param Comment $comment
     * @return void
     */
    public function delete(Comment $comment): void
    {
        $this->commentRepository->remove($comment);
    }

    /**
     * @param Comment $comment
     * @return void
     */
    public function setActivate(Comment $comment): void
    {
        $this->commentRepository->setActivate($comment);
    }

    /**
     * @param Comment $comment
     * @return void
     */
    public function setDeactivate(Comment $comment): void
    {
        $this->commentRepository->setDeactivate($comment);
    }

    /**
     * @param Comment $comment
     * @return void
     */
    public function create(Comment $comment): void
    {
        $this->commentRepository->save($comment);
    }
}