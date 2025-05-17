<?php

declare(strict_types=1);

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

/**
 *
 */
#[AsMessage('async')]
final readonly class NotifyAboutPostCommentMessage
{
    /**
     * @param string $postName
     */
    public function __construct(
        private string $postName
    ) {}

    /**
     * @return string
     */
    public function getPostName(): string
    {
        return $this->postName;
    }
}
