<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 *
 */
class BackRedirectService {

    private RequestStack $requestStack;
    private UrlGeneratorInterface $urlGenerator;

    /**
     * @param RequestStack $requestStack
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(RequestStack $requestStack, UrlGeneratorInterface $urlGenerator)
    {
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param string $fallbackRoute
     * @param array $parameters
     * @return RedirectResponse
     */
    public function back(string $fallbackRoute, array $parameters = []): RedirectResponse
    {
        $request = $this->requestStack->getCurrentRequest();
        $referer = $request?->headers->get('referer');

        return new RedirectResponse($referer ?? $this->urlGenerator->generate($fallbackRoute, $parameters));
    }
}