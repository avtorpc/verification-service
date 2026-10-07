<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class ClientIpResolver
{
    public function __construct(
        #[Autowire('%env(WEB_SERVICE_INTERNAL_TOKEN)%')]
        private readonly string $internalToken,
    ) {}

    public function resolve(Request $request): ?string
    {
        $ip = $request->headers->get('X-Web-Client-IP');
        $token = $request->headers->get('X-Web-Service-Token');

        // Direct requests retain the address supplied by Nginx/FastCGI.
        if ($ip === null && $token === null) {
            return $request->getClientIp();
        }

        if ($this->internalToken === '' || $token === null || !hash_equals($this->internalToken, $token)) {
            throw new AccessDeniedHttpException('Invalid internal caller credentials.');
        }

        if ($ip === null || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            throw new BadRequestHttpException('Invalid client IP address.');
        }

        return $ip;
    }
}
