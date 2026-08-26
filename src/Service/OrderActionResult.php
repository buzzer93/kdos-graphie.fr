<?php

declare(strict_types=1);

namespace App\Service;

final class OrderActionResult
{
    public function __construct(
        public readonly string $level,
        public readonly string $message,
    ) {
    }

    public static function success(string $message): self
    {
        return new self('success', $message);
    }

    public static function warning(string $message): self
    {
        return new self('warning', $message);
    }

    public static function danger(string $message): self
    {
        return new self('danger', $message);
    }

    public function isSuccess(): bool
    {
        return $this->level === 'success';
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
