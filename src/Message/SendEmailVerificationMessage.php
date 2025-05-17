<?php

declare(strict_types=1);

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

/**
 *
 */
#[AsMessage('async')]
final readonly class SendEmailVerificationMessage
{
    /**
     * @param int $userId
     */
    public function __construct(
        private int $userId
    ) {}

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }
}
