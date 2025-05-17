<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\CategoryService;
use Psr\Cache\InvalidArgumentException;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 *
 */
class AppExtension extends AbstractExtension implements GlobalsInterface
{

    /**
     * @param CategoryService $categoryService
     */
    public function __construct(private readonly CategoryService $categoryService)
    {
    }

    /**
     * @return array<string, mixed>
     * @throws InvalidArgumentException
     */
    public function getGlobals(): array
    {
        return [
            'global_categories' => $this->categoryService->findAllWithChildren(),
        ];
    }
}
