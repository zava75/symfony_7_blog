<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentType;
use App\Form\PostType;
use App\Message\NotifyAboutPostCommentMessage;
use App\Message\NotifyAdminAboutPostMessage;
use App\Service\BackRedirectService;
use App\Service\CommentService;
use App\Service\PostService;
use Doctrine\ORM\Exception\ORMException;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 *
 */
final class PostController extends AbstractController
{
    /**
     * @param PostService $postService
     * @param CommentService $commentService
     */
    public function __construct(
        private readonly PostService $postService,
        private readonly CommentService $commentService)
    {
    }

//    front

    /**
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
    #[Route('/search', name: 'post_search', methods: ['GET'], priority: 10,)]
    public function search(Request $request, PaginatorInterface $paginator): Response
    {
        $querySearch = $request->query->get('q');

        $query = $this->postService->searchPosts($querySearch);

        $posts = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('blog/search.html.twig', [
            'posts' => $posts,
            'querySearch' => $querySearch,
        ]);
    }

    /**
     * @param string $slug
     * @param Request $request
     * @param BackRedirectService $backRedirect
     * @param MessageBusInterface $bus
     * @return Response
     * @throws ExceptionInterface
     * @throws InvalidArgumentException
     * @throws ORMException
     */
    #[Route('/{category}/{subcategory}/{slug}', name: 'blog_post',
        requirements: ['slug' => '[a-z0-9-]+', 'category' => '[a-z0-9-]+', 'subcategory' => '[a-z0-9-]+'],
        methods: ['GET','POST'])]
    public function getPostBySlug(string $slug,
                                  Request $request,
                                  BackRedirectService $backRedirect,
                                  MessageBusInterface $bus): Response
    {
        $post = $this->postService->getPostBySlug($slug);

        if (!$post) {
            throw $this->createNotFoundException('Not Found');
        }

        $categoryId = $post->getCategory()->getId();
        $excludePostId = $post->getId();
        $posts = $this->postService->getActivePostsByCategoryLimit($categoryId, $excludePostId, 3);

        $comments = $this->commentService->getCommentsByPost($post->getId());

        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try {
                $comment->setPost($this->postService->getReferenceById($post->getId()));
            } catch (ORMException $e) {
                $this->addFlash('danger', 'Error when saving a comment. Try again later.');

                return $this->redirectToRoute('blog_post', [
                    'category' => $post->getCategory()->getParent()->getSlug(),
                    'subcategory' => $post->getCategory()->getSlug(),
                    'slug' => $post->getSlug(),
                ]);
            }

            $comment->setIsActive(false);
            $this->commentService->create($comment);

            $bus->dispatch(new NotifyAboutPostCommentMessage($post->getName()));

            $this->addFlash('success', 'Comment created successfully.');

            return $backRedirect->back('blog_post', [
                'category' => $post->getCategory()->getParent()->getSlug(),
                'subcategory' => $post->getCategory()->getSlug(),
                'slug' => $post->getSlug(),
            ]);
        }

        return $this->render('blog/post.html.twig', [
            'post' => $post,
            'posts' => $posts,
            'comments' => $comments,
            'form' => $form,
            ]);
    }

//    cabinet


    /**
     * @param Request $request
     * @param MessageBusInterface $bus
     * @return Response
     * @throws ExceptionInterface
     */
    #[Route('/cabinet/post/create', name: 'post_create', methods: ['GET', 'POST'], priority: 10)]
    public function create(Request $request, MessageBusInterface $bus): Response
    {
        $post = new Post();

        $form = $this->createForm(PostType::class, $post, ['is_edit' => false ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $imageFile = $form->get('image')->getData();

            $this->postService->create($post, $imageFile);

            $bus->dispatch(new NotifyAdminAboutPostMessage($post->getName(), 'create'));

            $this->addFlash('success', 'Post created successfully.');

            return $this->redirectToRoute('cabinet');
        }

        return $this->render('post/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param Request $request
     * @param Post $post
     * @param MessageBusInterface $bus
     * @param BackRedirectService $backRedirect
     * @return Response
     * @throws ExceptionInterface
     */
    #[Route('/cabinet/post/{id}/edit', name: 'post_edit', methods: ['GET', 'POST'], priority: 10)]
    public function edit(Request $request,
                         Post $post, MessageBusInterface $bus,
                         BackRedirectService $backRedirect): Response
    {
        $form = $this->createForm(PostType::class, $post , ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            $removeImage = $form->get('remove_image')->getData();

            $this->postService->update($post, $imageFile, $removeImage);

            $bus->dispatch(new NotifyAdminAboutPostMessage($post->getName(), 'update'));

            $this->addFlash('success', 'Post updated successfully.');

            return $backRedirect->back('cabinet');
        }

        return $this->render('post/edit.html.twig', [
            'form' => $form,
            'post' => $post,
        ]);
    }

    /**
     * @param Request $request
     * @param Post $post
     * @return Response
     */
    #[Route('/cabinet/post/{id}/delete', name: 'post_delete', methods: ['POST'])]
    public function delete(Request $request,
                           Post $post,
                           BackRedirectService $backRedirect): Response
    {
        if ($this->isCsrfTokenValid('delete-post-' . $post->getId(), $request->request->get('_token'))) {
            $this->postService->delete($post);
            $this->addFlash('success', 'Post deleted successfully.');
        }

        return $backRedirect->back('cabinet');
    }

//    admin

    /**
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
    #[Route('/admin/posts', name: 'admin_posts', methods: ['GET'], priority: 10)]
    public function getPostsAll(Request $request, PaginatorInterface $paginator): Response
    {
        $query = $this->postService->getPostsAll();
        $posts = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('post/index.html.twig', [
            'posts' => $posts,
        ]);
    }

//    api

    /**
     * @return Response
     */
    #[Route('/api/posts', name: 'api_posts', methods: ['GET'])]
    public function getPostsApi(): Response
    {
        $posts = $this->postService->getPosts();
        return $this->json($posts, 200, [], ['groups' => ['post:read']]);
    }

    /**
     * @param int $id
     * @return Response
     */
    #[Route('/api/posts/{id}', name: 'api_post', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getPostByIdApi(int $id): Response
    {

        $post = $this->postService->getPost($id);

        if (!$post) {
            return $this->json("Post not found",404);
        }

        return $this->json($post, 200, [], ['groups' => ['post:read']]);
    }
}
