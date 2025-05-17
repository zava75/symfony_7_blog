<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationForm;
use App\Message\SendEmailVerificationMessage;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

/**
 *
 */
class RegistrationController extends AbstractController
{

    /**
     * @param EmailVerifier $emailVerifier
     * @param UserService $userService
     */
    public function __construct(private readonly EmailVerifier $emailVerifier,
                                private readonly UserService   $userService)
    {
    }

    /**
     * @param Request $request
     * @param Security $security
     * @param MessageBusInterface $bus
     * @return Response
     * @throws ExceptionInterface
     */
    #[Route('/register', name: 'app_register', methods: ['GET' ,'POST'] , priority: 10)]
    public function register(Request $request,
                             Security $security,
                             MessageBusInterface $bus): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            $this->userService->save($user, $plainPassword);

            $bus->dispatch(new SendEmailVerificationMessage($user->getId()));

            $security->login($user, 'form_login', 'main');

            $this->addFlash('success', 'Please confirm your email address before logging in.');

            return $this->redirectToRoute('app_check_email');
        }

        return $this->render('security/registration.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    /**
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param UserRepository $userRepository
     * @return Response
     */
    #[Route('/verify/email', name: 'app_verify_email', methods: ['GET'], priority: 10)]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator, UserRepository $userRepository): Response
    {
        $id = $request->query->get('id');

        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($id);

        if (null === $user) {
            return $this->redirectToRoute('app_register');
        }

        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('cabinet');
    }

    /**
     * @return Response
     */
    #[Route('/check-email', name: 'app_check_email', methods: ['GET'],  priority: 10)]
    public function checkEmail(): Response
    {
        $user = $this->getUser();

        if ($user && $user->isVerified()) {
            $this->addFlash('info', 'Your email is already verified.');
            return $this->redirectToRoute('home');
        }

        return $this->render('security/check_email.html.twig');
    }
}
