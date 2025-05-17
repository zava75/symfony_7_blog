<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\UserService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 *
 */
final class UserController extends AbstractController
{
    /**
     * @param UserService $userService
     */
    public function __construct(private readonly UserService $userService)
    {
    }

//    admin

    /**
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
    #[Route('/admin/users', name: 'admin_users', methods: ['GET'] , priority: 10)]
    public function getUsers(Request $request, PaginatorInterface $paginator): Response
    {
        $query = $this->userService->getUsersAll();

        $users= $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('user/index.html.twig', [
            'users' => $users,
        ]);

    }

//    api

    /**
     * @return Response
     */
    #[Route('/api/users', name: 'users_api', methods: ['GET'], priority: 10)]
    public function getUsersApi(): Response
    {
        return $this->json($this->userService->getUsers(), 200, []);
    }

    /**
     * @param int $id
     * @return Response
     */
    #[Route('/api/user/{id}', name: 'user', requirements: ['id' => '\d+'], methods: ['GET'], priority: 10)]
    public function getUserById(int $id): Response
    {
        $user = $this->userService->getUser($id);

        if (!$user) {
            return $this->json("User not found",404);
        }

        return $this->json($user);
    }
}
