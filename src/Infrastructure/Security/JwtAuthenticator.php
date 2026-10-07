<?php

namespace App\Infrastructure\Security;

use App\Shared\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Request;

final class JwtAuthenticator
{
    public function __construct(
        private readonly JwtTokenDecoder $decoder
    ) {}

    public function authenticate(Request $request): object
    {
        $header = $request->headers->get('Authorization');

        if (!$header) {
            throw new ValidationException('Authorization header required');
        }

        if (!str_starts_with($header, 'Bearer ')) {
            throw new ValidationException('Invalid authorization header');
        }

        return $this->decoder->decode(substr($header, 7));
    }
}
