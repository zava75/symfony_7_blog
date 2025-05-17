<?php

namespace App\MessageHandler;

use App\Message\NotifyAboutPostCommentMessage;
use App\Service\EmailFactoryService;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 *
 */
#[AsMessageHandler]
final readonly class NotifyAboutPostCommentMessageHandler
{
    /**
     * @param EmailFactoryService $emailFactoryService
     * @param MailerInterface $mailer
     */
    public function __construct(
        private EmailFactoryService $emailFactoryService,
        private MailerInterface     $mailer,
    ) {}

    /**
     * @param NotifyAboutPostCommentMessage $message
     * @return void
     * @throws TransportExceptionInterface
     */
    public function __invoke(NotifyAboutPostCommentMessage $message): void
    {
        $email = $this->emailFactoryService->createAdminNotificationAboutComment($message->getPostName());
        $this->mailer->send($email);
    }

}
