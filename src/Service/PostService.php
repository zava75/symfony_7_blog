<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Post;
use Doctrine\ORM\Exception\ORMException;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\KernelInterface;
use App\Repository\PostRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 *
 */
readonly class PostService
{

    /**
     * @param PostRepository $postRepository
     * @param KernelInterface $kernel
     * @param SluggerInterface $slugger
     * @param Security $security
     * @param int $cacheTtl
     * @param TagAwareCacheInterface $cache
     */
    public function __construct(
        private PostRepository   $postRepository,
        private KernelInterface  $kernel,
        private SluggerInterface $slugger,
        private Security         $security,
        private int              $cacheTtl,
        private TagAwareCacheInterface $cache)
    {
    }

    /**
     * @return array
     */
    public function getPosts(): array
    {
        return $this->postRepository->findAll();
    }

    /**
     * @param int $id
     * @return Post|null
     */
    public function getPost(int $id): ?Post
    {
        return $this->postRepository->findOneBy(['id' => $id]);
    }

    /**
     * @param int $limit
     * @return array
     */
    public function getPostsLaster(int $limit): array
    {
        return $this->postRepository->findLatestPosts($limit);
    }


    /**
     * @param int $page
     * @param int $limit
     * @param PaginatorInterface $paginator
     * @return PaginationInterface
     * @throws InvalidArgumentException
     */
    public function getPaginatedLatestPosts(int $page, int $limit, PaginatorInterface $paginator): PaginationInterface
    {
        $cacheKey = sprintf('getPaginatedLatestPosts_%d_l_%d', $page, $limit);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($page, $limit, $paginator) {
            $item->tag('posts');
            $item->expiresAfter($this->cacheTtl);

            $query = $this->postRepository->createLatestPostsQuery();

            return $paginator->paginate($query, $page, $limit);
        });
    }

    /**
     * @param string $slug
     * @return Post|null
     * @throws InvalidArgumentException
     */
    public function getPostBySlug(string $slug): ?Post
    {
       return $this->cache->get('getPostBySlug' . $slug, function (ItemInterface $item) use ($slug) {
            $item->tag('post');
            $item->expiresAfter($this->cacheTtl);
            return $this->postRepository->findOneIsActive($slug);
        });
    }

    /**
     * @param array $categoryIds
     * @param $pageNumber
     * @param int $limit
     * @param PaginatorInterface $paginator
     * @return PaginationInterface
     * @throws InvalidArgumentException
     */
    public function findPostsByCategoryIds(array $categoryIds, $pageNumber, int $limit, PaginatorInterface $paginator ): PaginationInterface
    {
        $idsHash = md5(json_encode($categoryIds));

        $cacheKey = sprintf('findPostsByCategoryIds_%d_l_%d_i_%s', $pageNumber, $limit, $idsHash);
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($categoryIds, $pageNumber, $limit, $paginator) {
            $item->tag('posts');
            $item->expiresAfter($this->cacheTtl);

            $query = $this->postRepository->findPostsByCategoryIds($categoryIds);

            return $paginator->paginate($query, $pageNumber, $limit);
        });
    }


    /**
     * @param int $userId
     * @return QueryBuilder
     */
    public function getPostsUser(int $userId): QueryBuilder
    {
        return $this->postRepository->findPostsUser($userId);
    }

    /**
     * @return QueryBuilder
     */
    public function getPostsAll(): QueryBuilder
    {
        return $this->postRepository->findPostsAll();
    }

    /**
     * @param Post $post
     * @param UploadedFile|null $imageFile
     * @return void
     */
    public function create(Post $post, ?UploadedFile $imageFile = null): void
    {
        if ($imageFile) {
            $this->saveNewImage($post, $imageFile);
        }

        $user = $this->security->getUser();
        $post->setUser($user);

        if (!in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            $post->setIsActive(false);
        } else {
            if (!$post->IsActive()) {
                $post->setIsActive(true);
            }
        }

        $slug = $this->generateUniqueSlug($post->getName());
        $post->setSlug($slug);

        $this->postRepository->save($post);
    }

    /**
     * @param Post $post
     * @param UploadedFile|null $imageFile
     * @param bool $removeImage
     * @return void
     */
    public function update(Post $post, ?UploadedFile $imageFile = null, bool $removeImage = false): void
    {
        if ($removeImage) {
            $this->removeOldImage($post);
            $post->setImage(null);
        }

        if ($imageFile) {
            $this->removeOldImage($post);
            $this->saveNewImage($post, $imageFile);
        }

        $user = $post->getUser();

        if (!in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            $post->setIsActive(false);
        } else {
            if (!$post->IsActive()) {
                $post->setIsActive(true);
            }
        }

        $slug = $this->generateUniqueSlug($post->getName());
        $post->setSlug($slug);

        $this->postRepository->save($post);
    }

    /**
     * @param Post $post
     * @return void
     */
    private function removeOldImage(Post $post): void
    {
        $oldImage = $post->getImage();
        if ($oldImage && !str_starts_with($oldImage, 'demo/')) {
            $oldImagePath = $this->kernel->getProjectDir() . '/public/uploads/images/' . $oldImage;
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }
    }

    /**
     * @param Post $post
     * @param UploadedFile $imageFile
     * @return void
     */
    private function saveNewImage(Post $post, UploadedFile $imageFile): void
    {
        $fileName = uniqid() . '.' . $imageFile->guessExtension();
        $relativePath = 'blog/' . $fileName;
        $destination = $this->kernel->getProjectDir() . '/public/uploads/images/blog';

        $imageFile->move($destination, $fileName);
        $post->setImage($relativePath);
    }

    /**
     * @param Post $post
     * @return void
     */
    public function delete(Post $post): void
    {
        $this->removeOldImage($post);
        $this->postRepository->remove($post);
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

        while ($this->postRepository->findOneBy(['slug' => $slug])) {
            if ($i == 1) {
                $slug = $baseSlug . '-' . $i;
            }
            $i++;
        }

        return $slug;
    }

    /**
     * @param string $search
     * @return QueryBuilder
     */
    public function searchPosts(string $search): QueryBuilder
    {
        return $this->postRepository->searchPosts($search);
    }


    /**
     * @param int $categoryId
     * @param int $excludePostId
     * @param int $limit
     * @return array
     * @throws InvalidArgumentException
     */
    public function getActivePostsByCategoryLimit(int $categoryId, int $excludePostId, int $limit):array
    {
        $keyUniq = $categoryId . '_' . $excludePostId . '_' . $limit;

        return $this->cache->get('getActivePostsByCategoryLimit' . $keyUniq,
            function (ItemInterface $item) use ($limit, $excludePostId, $categoryId) {
                $item->tag('post');
                $item->expiresAfter($this->cacheTtl
            );

            return $this->postRepository->findActivePostsByCategoryLimit($categoryId, $excludePostId, $limit);
        });
    }

    /**
     * @param int $id
     * @return Post
     * @throws ORMException
     */
    public function getReferenceById(int $id) : Post
    {
        return $this->postRepository->getReferenceById($id);
    }
}