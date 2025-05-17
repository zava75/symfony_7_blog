<?php

declare(strict_types=1);

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

/**
 *
 */
#[AsMessage('async')]
final readonly class NotifyAdminAboutPostMessage
{
    /**
     * @param string $namePost
     * @param string $action
     */
    public function __construct(
        private string $namePost,
        private string $action,
    ) {}

    /**
     * @return string
     */
    public function getNamePost(): string
    {
        return $this->namePost;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

}
