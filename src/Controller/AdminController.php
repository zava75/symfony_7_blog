<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\CacheClearService;
use App\Service\CategoryService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 *
 */
final class AdminController extends AbstractController
{

    /**
     * @param CacheClearService $cacheClearService
     * @param CategoryService $categoryService
     */
    public function __construct(
        private readonly CacheClearService $cacheClearService,
        private readonly CategoryService  $categoryService)
    {
    }

    /**
     * @return Response
     */
    #[Route('/admin', name: 'admin', methods: ['GET'], priority: 10)]
    public function index(): Response
    {
        $categories = $this->categoryService->getAllWithPostCounts();

        return $this->render('admin/index.html.twig', ['categories' => $categories]);
    }

    /**
     * @return Response
     */
    #[Route('/admin/clear-cache/all', name: 'admin_clear_cache_all', methods: ['POST'])]
    public function clearAllCache(): Response
    {
        $this->cacheClearService->clearAll();
        $this->addFlash('success', 'The cache is completely cleared.');
        return $this->redirectToRoute('admin');
    }

    /**
     * @return Response
     * @throws InvalidArgumentException
     */
    #[Route('/admin/clear-cache/posts', name: 'admin_clear_cache_post', methods: ['POST'])]
    public function clearPostCache(): Response
    {
        $this->cacheClearService->clearByTag('post');
        $this->addFlash('success', 'The post cache has been cleared.');
        return $this->redirectToRoute('admin');
    }

    /**
     * @return Response
     * @throws InvalidArgumentException
     */
    #[Route('/admin/clear-cache/categories', name: 'admin_clear_cache_category', methods: ['POST'])]
    public function clearCategoryCache(): Response
    {
        $this->cacheClearService->clearByTag('category');
        $this->addFlash('success', 'The category cache has been cleared.');
        return $this->redirectToRoute('admin');
    }

}
