<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class RecaptchaService
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
    private const MIN_SCORE = 0.5;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(string:RECAPTCHA_SECRET_KEY)%')]
        private readonly string $secretKey,
    ) {
    }

    public function isValid(string $token, string $remoteIp): bool
    {
        if ($this->secretKey === '') {
            return true;
        }

        if ($token === '') {
            return false;
        }

        try {
            $response = $this->httpClient->request('POST', self::VERIFY_URL, [
                'body' => [
                    'secret' => $this->secretKey,
                    'response' => $token,
                    'remoteip' => $remoteIp,
                ],
            ]);

            $data = $response->toArray(throw: false);
        } catch (\Throwable) {
            return false;
        }

        return ($data['success'] ?? false) === true
            && ($data['score'] ?? 0.0) >= self::MIN_SCORE;
    }
}
