<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\PostService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

/**
 *
 */
final class CabinetController extends AbstractController
{
    /**
     * @param PostService $postService
     */
    public function __construct(private readonly postService $postService){

    }

    /**
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
    #[Route('/cabinet', name: 'cabinet', methods: ['GET'], priority: 10)]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in to access this page.');
        }

        $query = $this->postService->getPostsUser($user->getId());
//        TODO

        $posts = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('cabinet/index.html.twig', [ 'user' => $user, 'posts' => $posts]);
    }

}
