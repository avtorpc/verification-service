<?php

declare(strict_types=1);

require dirname(__DIR__,3).'/services/web-service/vendor/autoload.php';
require dirname(__DIR__,3).'/services/verification-service/src/Infrastructure/Http/ClientIpResolver.php';

use App\ApiClient\VerificationClient;
use App\Infrastructure\Http\ClientIpResolver;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

function check(bool $ok, string $message): void {
    if (!$ok) { throw new RuntimeException($message); }
}
function rejected(ClientIpResolver $resolver, Request $request, int $status): void {
    try { $resolver->resolve($request); }
    catch (HttpExceptionInterface $e) {
        check($e->getStatusCode() === $status, 'Unexpected rejection status');
        return;
    }
    throw new RuntimeException('Untrusted input accepted');
}

Request::setTrustedProxies([], 0);
$token = 'test-only-internal-key';
$resolver = new ClientIpResolver($token);
$browser = Request::create('/', server: ['REMOTE_ADDR' => '203.0.113.17', 'HTTP_X_FORWARDED_FOR' => '192.0.2.99']);
check($resolver->resolve($browser) === '203.0.113.17', 'Untrusted forwarded IP accepted');
$forged = clone $browser;
$forged->headers->set('X-Web-Client-IP', '192.0.2.33');
rejected($resolver, $forged, 403);
$forged->headers->set('X-Web-Service-Token', 'wrong');
rejected($resolver, $forged, 403);
$forged->headers->set('X-Web-Service-Token', $token);
check($resolver->resolve($forged) === '192.0.2.33', 'Trusted IPv4 rejected');
$forged->headers->set('X-Web-Client-IP', '2001:db8::1');
check($resolver->resolve($forged) === '2001:db8::1', 'Trusted IPv6 rejected');
rejected(new ClientIpResolver(''), $forged, 403);
$forged->headers->set('X-Web-Client-IP', '192.0.2.1, 192.0.2.2');
rejected($resolver, $forged, 400);
$forged->headers->remove('X-Web-Client-IP');
rejected($resolver, $forged, 400);

// Pass the actual HTTP client's generated headers through the receiving resolver.
$browser->headers->set('X-Web-Client-IP', '192.0.2.66');
$browser->headers->set('X-Web-Service-Token', 'browser-forgery');
$stack = new RequestStack();
$stack->push($browser);
$seen = [];
$http = new MockHttpClient(function ($method, $url, $options) use ($resolver, &$seen) {
    check($method === 'POST', 'Wrong method');
    check($options['max_redirects'] === 0, 'Redirect might expose internal credential');
    $incoming = Request::create($url, 'POST', server: ['REMOTE_ADDR' => '172.20.0.4']);
    foreach ($options['headers'] as $header) {
        [$name, $value] = explode(':', $header, 2);
        $incoming->headers->set($name, trim($value));
    }
    check($resolver->resolve($incoming) === '203.0.113.17', 'Browser IP not preserved end-to-end');
    check(json_decode($options['body'], true) === ['requestId' => 'test'], 'Body changed');
    $seen[] = parse_url($url, PHP_URL_PATH);
    return new MockResponse('{"success":true}', ['http_code' => 200]);
});
$client = new VerificationClient($http, $stack, 'http://nginx/api/registration', $token);
foreach (['register', 'verifyEmail', 'resendEmail'] as $method) {
    check($client->$method(['requestId' => 'test'])->toArray()['success'], 'Response not retained');
}
check($seen === ['/api/registration/quick-signup', '/api/registration/check-code-email', '/api/registration/resend-code-email'], 'Wrong endpoints');
foreach ([new VerificationClient($http, $stack, 'http://nginx', ''), new VerificationClient($http, new RequestStack(), 'http://nginx', $token)] as $invalidClient) {
    try { $invalidClient->register([]); }
    catch (LogicException) { continue; }
    throw new RuntimeException('Incomplete caller context accepted');
}
echo "PASS: direct IP, IPv4/IPv6, spoof rejection, disabled credentials, invalid IP, all three HTTP paths, missing context.\n";
