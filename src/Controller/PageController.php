<?php

namespace App\Controller;

use App\Entity\Page;
use App\Form\PageType;
use App\Service\CategoryService;
use App\Service\PageService;
use App\Service\PostService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

/**
 *
 */
final class PageController extends AbstractController
{
    /**
     * @param PageService $pageService
     * @param PostService $postService
     * @param CategoryService $categoryService
     */
    public function __construct(
        private readonly PageService     $pageService,
        private readonly PostService     $postService,
        private readonly CategoryService $categoryService
    )
    {
    }

//    front

    /**
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     * @throws InvalidArgumentException
     */
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $page = $this->pageService->getPage('home');
        $categories = $this->categoryService->getCategories();

        $pageNumber = $request->query->getInt('page', 1);

        $posts = $this->postService->getPaginatedLatestPosts($pageNumber, 10, $paginator);

        return $this->render('blog/home.html.twig', [
            'page' => $page,
            'posts' => $posts,
            'categories' => $categories,
        ]);
    }


    /**
     * @param string $slug
     * @return Response
     * @throws InvalidArgumentException
     */
    #[Route('/information/{slug}', name: 'show-page', methods: ['GET'], priority: 10)]
    public function showPage(string $slug): Response
    {
        $page = $this->pageService->getPage($slug);

        if (!$page || $slug === 'home') {
            throw $this->createNotFoundException('Not Found');
        }

        return $this->render('page/show.html.twig', [
            'page' => $page,
        ]);
    }

//    admin

    /**
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
    #[Route('/admin/pages', name: 'admin_pages', methods: ['GET'], priority: 10)]
    public function showPageAdmin(Request $request, PaginatorInterface $paginator): Response
    {
        $query = $this->pageService->getPagesAll();

        $pages = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('page/index.html.twig', [
            'pages' => $pages,
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/admin/page/create', name: 'page_create', methods: ['GET', 'POST'], priority: 10)]
    public function createPage(Request $request): Response
    {
        $page = new Page();

        $form = $this->createForm(PageType::class, $page);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->pageService->create($page);

            $this->addFlash('success', 'Page created successfully.');

            return $this->redirectToRoute('admin_pages');
        }

        return $this->render('page/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param Request $request
     * @param Page $page
     * @return Response
     */
    #[Route('/admin/page/{id}/edit', name: 'page_edit', methods: ['GET', 'POST'], priority: 10)]
    public function edit(Request $request, Page $page): Response
    {
        $form = $this->createForm(PageType::class, $page);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->pageService->update($page);

            $this->addFlash('success', 'Page updated successfully.');

            return $this->redirectToRoute('admin_pages');
        }

        return $this->render('page/edit.html.twig', [
            'form' => $form
        ]);
    }

    /**
     * @param Request $request
     * @param Page $page
     * @return Response
     */
    #[Route('/admin/page/{id}/delete', name: 'page_delete', methods: ['POST'])]
    public function delete(Request $request, Page $page): Response
    {
        if ($this->isCsrfTokenValid('delete-page-' . $page->getId(), $request->request->get('_token'))) {
            $this->pageService->delete($page);
            $this->addFlash('success', 'Page deleted successfully.');
        }

        return $this->redirectToRoute('admin_pages');
    }

}
