<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\SendEmailVerificationMessage;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\EmailFactoryService;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 *
 */
#[AsMessageHandler]
final readonly class SendEmailVerificationMessageHandler
{
    /**
     * @param UserRepository $userRepository
     * @param EmailVerifier $emailVerifier
     * @param EmailFactoryService $emailFactoryService
     */
    public function __construct(
        private UserRepository      $userRepository,
        private EmailVerifier       $emailVerifier,
        private EmailFactoryService $emailFactoryService
    ) {}

    /**
     * @param SendEmailVerificationMessage $message
     * @return void
     */
    public function __invoke(SendEmailVerificationMessage $message): void
    {
        $user = $this->userRepository->find($message->getUserId());

        if (!$user) {
            return;
        }

        $email = $this->emailFactoryService->createEmailConfirmation($user);

        try {
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user, $email);
        } catch (TransportExceptionInterface $e) {
            // TODO log failed transport
        }
    }
}
