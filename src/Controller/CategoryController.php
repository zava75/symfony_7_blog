<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Service\CategoryService;
use App\Service\PostService;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 *
 */
final class CategoryController extends AbstractController
{
    /**
     * @param CategoryService $categoryService
     * @param PostService $postService
     */
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly PostService $postService)
    {
    }


//    front

    /**
     * @param string $slug
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     * @throws InvalidArgumentException
     */
    #[Route('/{slug}', name: 'blog_category', requirements: ['slug' => '[a-z0-9-]+'] , methods: ['GET'])]
    public function getCategoryBySlug(string $slug, Request $request, PaginatorInterface $paginator): Response
    {

        $category = $this->categoryService->getCategoryActiveBySlug($slug);

        if (!$category) {
            throw $this->createNotFoundException('Not Found');
        }

        $categoryIds = [$category->getId()];

        foreach ($category->getChildren() as $child) {
            $categoryIds[] = $child->getId();
        }

        $pageNumber = $request->query->getInt('page', 1);

        $posts = $this->postService->findPostsByCategoryIds($categoryIds, $pageNumber, 10, $paginator);

        return $this->render('blog/category.html.twig', [
            'category' => $category,
            'posts' => $posts,
        ]);

    }

//    admin

    /**
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
    #[Route('/admin/categories', name: 'admin_categories', methods: ['GET'], priority: 10)]
    public function getCategories(Request $request, PaginatorInterface $paginator): Response
    {
        $query = $this->categoryService->getCategories();

        $categories = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/admin/category/create', name: 'category_create', methods: ['GET', 'POST'], priority: 10)]
    public function create(Request $request): Response
    {
        $category = new Category();

        $form = $this->createForm(CategoryType::class, $category);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->categoryService->create($category);

            $this->addFlash('success', 'Category created successfully.');

            return $this->redirectToRoute('admin_categories');
        }

        return $this->render('category/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param Request $request
     * @param Category $category
     * @return Response
     */
    #[Route('/admin/category/{id}/edit', name: 'category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category): Response
    {
        $form = $this->createForm(CategoryType::class, $category);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->categoryService->update($category);

            $this->addFlash('success', 'Category updated successfully.');

            return $this->redirectToRoute('admin_categories');
        }

        return $this->render('category/edit.html.twig', [
            'form' => $form
        ]);
    }

    /**
     * @param int $id
     * @return Response
     */
    #[Route('/admin/categories/{id}', name: 'category', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getCategoryById(int $id): Response
    {

        $category = $this->categoryService->getCategory($id);

        return $this->render('category/show.html.twig', [
            'category' => $category,
        ]);

    }

    /**
     * @param Request $request
     * @param Category $category
     * @return Response
     */
    #[Route('/admin/category/{id}/delete', name: 'category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category): Response
    {
        if ($this->isCsrfTokenValid('delete-category-' . $category->getId(), $request->request->get('_token'))) {
            $this->categoryService->delete($category);
            $this->addFlash('success', 'Category deleted successfully.');
        }

        return $this->redirectToRoute('admin_categories');
    }

//    api

    /**
     * @return Response
     */
    #[Route('/api/categories', name: 'api_categories', methods: ['GET'],  priority: 10)]
    public function getCategoriesApi(): Response
    {
        $categories = $this->categoryService->getCategories();
        return $this->json($categories, 200, [], ['groups' => ['category:read']]);
    }

    /**
     * @param int $id
     * @return Response
     */
    #[Route('/api/categories/{id}', name: 'api_category', requirements: ['id' => '\d+'], methods: ['GET'], priority: 10)]
    public function getCategoryByIdApi(int $id): Response
    {

        $category = $this->categoryService->getCategory($id);

        if (!$category) {
            return $this->json("Category not found",404);
        }

        return $this->json($category, 200, [], ['groups' => ['category:read']]);
    }

}
