<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final class QuotePdfStorage
{
    public const MAX_FILE_SIZE_BYTES = 10 * 1024 * 1024;
    private const DIRECTORY = 'var/uploads/quotes';

    public function __construct(private readonly string $projectDir)
    {
    }

    public function store(UploadedFile $file): string
    {
        $targetDirectory = $this->getAbsoluteDirectory();
        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0775, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.pdf';
        $file->move($targetDirectory, $filename);

        return $filename;
    }

    public function getAbsolutePath(?string $filename): ?string
    {
        if ($filename === null || $filename === '') {
            return null;
        }

        return $this->getAbsoluteDirectory() . DIRECTORY_SEPARATOR . $filename;
    }

    public function remove(?string $filename): void
    {
        if ($filename === null || $filename === '') {
            return;
        }

        $path = $this->getAbsoluteDirectory() . DIRECTORY_SEPARATOR . $filename;
        if (is_file($path)) {
            unlink($path);
        }
    }

    private function getAbsoluteDirectory(): string
    {
        return $this->projectDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, self::DIRECTORY);
    }
}
