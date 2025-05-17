<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\NotifyAdminAboutPostMessage;
use App\Service\EmailFactoryService;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mailer\MailerInterface;

/**
 *
 */
#[AsMessageHandler]
final readonly class NotifyAdminAboutPostMessageHandler
{
    /**
     * @param EmailFactoryService $emailFactory
     * @param MailerInterface $mailer
     */
    public function __construct(
        private EmailFactoryService $emailFactory,
        private MailerInterface     $mailer,
    )
    {
    }

    /**
     * @param NotifyAdminAboutPostMessage $message
     * @return void
     * @throws TransportExceptionInterface
     */
    public function __invoke(NotifyAdminAboutPostMessage $message): void
    {
        $email = $this->emailFactory->sendNotificationAdminPost(
            $message->getNamePost(),
            $message->getAction()
        );

        $this->mailer->send($email);
    }
}
