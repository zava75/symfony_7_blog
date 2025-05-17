<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

/**
 *
 */
readonly class EmailFactoryService {

    /**
     * @param string $adminEmail
     */
    public function __construct(private string $adminEmail)
    {
    }

    /**
     * @param User $user
     * @return TemplatedEmail
     */
    public function createEmailConfirmation(User $user): TemplatedEmail
    {
        return (new TemplatedEmail())
            ->from(new Address($this->adminEmail, 'no-reply'))
            ->to($user->getEmail())
            ->subject('Please Confirm your Email')
            ->htmlTemplate('emails/confirmation_email.html.twig');
    }

    /**
     * @param string $namePost
     * @param string $action
     * @return TemplatedEmail
     */
    public function sendNotificationAdminPost(string $namePost, string $action): TemplatedEmail
    {
        $subject = $action === 'create'
            ? 'A new post has been created and is awaiting moderation'
            : 'The post has been updated and is awaiting moderation';

        return (new TemplatedEmail())
            ->from(new Address($this->adminEmail, 'no-reply'))
            ->to($this->adminEmail)
            ->subject($subject)
            ->htmlTemplate('emails/notification_new_post.html.twig')
            ->context([
                'namePost' => $namePost,
            ]);
    }

    /**
     * @param string $namePost
     * @return TemplatedEmail
     */
    public function createAdminNotificationAboutComment(string $namePost): TemplatedEmail
    {
        return (new TemplatedEmail())
            ->from(new Address($this->adminEmail, 'no-reply'))
            ->to($this->adminEmail)
            ->subject('New comment posted')
            ->htmlTemplate('emails/notification_new_comment_post.html.twig')
            ->context([
                'namePost' => $namePost,
            ]);
    }

}