<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProductImageStorage
{
    private const DIRECTORY = 'assets/media/products';

    public function __construct(private readonly string $projectDir)
    {
    }

    public function store(?UploadedFile $file, ?string $currentFilename = null): ?string
    {
        if ($file === null) {
            return $currentFilename;
        }

        $targetDirectory = $this->getAbsoluteDirectory();
        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0775, true);
        }

        $extension = $file->guessExtension();
        $filename = bin2hex(random_bytes(12)) . '.' . ($extension ?: 'bin');

        $file->move($targetDirectory, $filename);

        if ($currentFilename !== null) {
            $this->remove($currentFilename);
        }

        return $filename;
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

    public function getPublicPath(?string $filename): ?string
    {
        if ($filename === null || $filename === '') {
            return null;
        }

        return '/' . self::DIRECTORY . '/' . $filename;
    }

    private function getAbsoluteDirectory(): string
    {
        return $this->projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, self::DIRECTORY);
    }
}
