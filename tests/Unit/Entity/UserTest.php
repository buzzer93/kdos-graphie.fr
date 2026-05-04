<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testRolesAlwaysContainRoleUserAndSerializeHashesPassword(): void
    {
        $user = (new User())
            ->setEmail('admin@example.test')
            ->setRoles(['ROLE_ADMIN'])
            ->setPassword('very-secret-hash');

        self::assertSame('admin@example.test', $user->getUserIdentifier());
        self::assertContains('ROLE_ADMIN', $user->getRoles());
        self::assertContains('ROLE_USER', $user->getRoles());

        $serialized = $user->__serialize();
        $passwordKey = "\0" . User::class . "\0password";

        self::assertArrayHasKey($passwordKey, $serialized);
        self::assertSame(hash('crc32c', 'very-secret-hash'), $serialized[$passwordKey]);
    }
}
