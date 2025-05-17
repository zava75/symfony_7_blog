<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Service\BackRedirectService;
use App\Service\CommentService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 *
 */
final class CommentController extends AbstractController
{
    /**
     * @param CommentService $commentService
     */
    public function __construct(private readonly CommentService $commentService)
    {
    }


    /**
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
    #[Route('/admin/comments', name: 'admin_comments', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $query = $this->commentService->getCommentsAll();

        $comments = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('comment/index.html.twig', [
            'comments' => $comments,
        ]);
    }

    /**
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
    #[Route('/admin/comments/not-active', name: 'admin_comments_not_active', methods: ['GET'])]
    public function indexNotActive(Request $request, PaginatorInterface $paginator): Response
    {
        $query = $this->commentService->getCommentsNotActive();

        $comments = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('comment/index-not-active.html.twig', [
            'comments' => $comments,
        ]);
    }

    /**
     * @param Request $request
     * @param Comment $comment
     * @param BackRedirectService $backRedirect
     * @return Response
     */
    #[Route('/admin/comment/{id}/delete', name: 'comment_delete', methods: ['POST'])]
    public function delete(Request $request, Comment $comment, BackRedirectService $backRedirect): Response
    {
        if ($this->isCsrfTokenValid('delete-comment-' . $comment->getId(), $request->request->get('_token'))) {
            $this->commentService->delete($comment);
            $this->addFlash('success', 'Comment deleted successfully.');
        }

        return $backRedirect->back('admin_comments');
    }


    /**
     * @param Request $request
     * @param Comment $comment
     * @param BackRedirectService $backRedirect
     * @return Response
     */
    #[Route('/admin/comment/{id}/activate', name: 'comment_activate', methods: ['POST'])]
    public function setActiveComment(Request $request, Comment $comment, BackRedirectService $backRedirect): Response
    {
        if ($this->isCsrfTokenValid('activate-comment-' . $comment->getId(), $request->request->get('_token'))) {
            $this->commentService->setActivate($comment);
            $this->addFlash('success', 'Comment set active successfully.');
        }

        return $backRedirect->back('admin_comments');
    }

    /**
     * @param Request $request
     * @param Comment $comment
     * @param BackRedirectService $backRedirect
     * @return Response
     */
    #[Route('/admin/comment/{id}/deactivate', name: 'comment_deactivate', methods: ['POST'])]
    public function setDeactivateComment(Request $request, Comment $comment, BackRedirectService $backRedirect): Response
    {
        if ($this->isCsrfTokenValid('deactivate-comment-' . $comment->getId(), $request->request->get('_token'))) {
            $this->commentService->setDeactivate($comment);
            $this->addFlash('success', 'Comment set deactivate successfully.');
        }

        return $backRedirect->back('admin_comments');
    }

}
