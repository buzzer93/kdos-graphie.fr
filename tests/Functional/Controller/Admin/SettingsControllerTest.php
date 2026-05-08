<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Functional\AbstractWebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class SettingsControllerTest extends AbstractWebTestCase
{
    private const ADMIN_PASSWORD = 'correct-password';

    public function testIndexRequiresAuthentication(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $client->request('GET', '/admin/settings/');

        self::assertResponseRedirects('/login');
    }

    public function testIndexDisplaysContactSettings(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/settings/');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="settings[contact_phone]"]');
        self::assertSelectorExists('input[name="settings[contact_email]"]');
        self::assertSelectorExists('input[name="settings[contact_instagram]"]');
        self::assertSelectorExists('input[name="settings[contact_facebook]"]');
    }

    public function testContactSettingsCanBeUpdated(): void
    {
        $client = $this->createAuthenticatedClient();

        $crawler = $client->request('GET', '/admin/settings/');
        $token = (string) $crawler->filter('form[action="/admin/settings/"] input[name="_token"]')->first()->attr('value');

        $client->request('POST', '/admin/settings/', [
            '_token' => $token,
            'settings' => [
                'contact_phone'     => '06 12 34 56 78',
                'contact_email'     => 'contact@kdos.test',
                'contact_address'   => '1 rue du Graphisme',
                'contact_instagram' => 'https://instagram.com/kdos',
                'contact_facebook'  => 'https://facebook.com/kdos',
            ],
        ]);

        self::assertResponseRedirects('/admin/settings/');
        $client->followRedirect();
        self::assertStringContainsString('06 12 34 56 78', (string) $client->getResponse()->getContent());
        self::assertStringContainsString('contact@kdos.test', (string) $client->getResponse()->getContent());
    }

    public function testAddNewSetting(): void
    {
        $client = $this->createAuthenticatedClient();

        $crawler = $client->request('GET', '/admin/settings/');
        $token = (string) $crawler->filter('form[action="/admin/settings/new"] input[name="_token"]')->attr('value');

        $client->request('POST', '/admin/settings/new', [
            '_token'      => $token,
            'setting_key' => 'custom_key',
            'label'       => 'Custom Label',
        ]);

        self::assertResponseRedirects('/admin/settings/');
        $client->followRedirect();
        self::assertStringContainsString('custom_key', (string) $client->getResponse()->getContent());
    }

    public function testAddNewSettingRequiresKeyAndLabel(): void
    {
        $client = $this->createAuthenticatedClient();

        $crawler = $client->request('GET', '/admin/settings/');
        $token = (string) $crawler->filter('form[action="/admin/settings/new"] input[name="_token"]')->attr('value');

        $client->request('POST', '/admin/settings/new', [
            '_token'      => $token,
            'setting_key' => '',
            'label'       => '',
        ]);

        self::assertResponseRedirects('/admin/settings/');
        $client->followRedirect();
        self::assertStringContainsString('obligatoires', (string) $client->getResponse()->getContent());
    }

    public function testChangeEmailSuccessRedirectsToLogin(): void
    {
        $client = $this->createAuthenticatedClientWithRealPassword();

        $crawler = $client->request('GET', '/admin/settings/');
        $token = (string) $crawler->filter('form[action="/admin/settings/change-email"] input[name="_token"]')->attr('value');

        $client->request('POST', '/admin/settings/change-email', [
            '_token'           => $token,
            'current_password' => self::ADMIN_PASSWORD,
            'new_email'        => 'new-admin@test.local',
        ]);

        self::assertResponseRedirects('/login');

        $userRepository = static::getContainer()->get(UserRepository::class);
        self::assertNotNull($userRepository->findOneBy(['email' => 'new-admin@test.local']));
    }

    public function testChangeEmailRejectsWrongCurrentPassword(): void
    {
        $client = $this->createAuthenticatedClientWithRealPassword();

        $crawler = $client->request('GET', '/admin/settings/');
        $token = (string) $crawler->filter('form[action="/admin/settings/change-email"] input[name="_token"]')->attr('value');

        $client->request('POST', '/admin/settings/change-email', [
            '_token'           => $token,
            'current_password' => 'wrong-password',
            'new_email'        => 'new-admin@test.local',
        ]);

        self::assertResponseRedirects('/admin/settings/');
        $client->followRedirect();
        self::assertStringContainsString('incorrect', (string) $client->getResponse()->getContent());
    }

    public function testChangeEmailRejectsDuplicateEmail(): void
    {
        $client = $this->createAuthenticatedClientWithRealPassword();

        $crawler = $client->request('GET', '/admin/settings/');
        $token = (string) $crawler->filter('form[action="/admin/settings/change-email"] input[name="_token"]')->attr('value');

        $client->request('POST', '/admin/settings/change-email', [
            '_token'           => $token,
            'current_password' => self::ADMIN_PASSWORD,
            'new_email'        => 'admin-real@test.local',
        ]);

        self::assertResponseRedirects('/admin/settings/');
        $client->followRedirect();
        self::assertStringContainsString('déjà utilisée', (string) $client->getResponse()->getContent());
    }

    public function testChangeEmailRejectsInvalidEmail(): void
    {
        $client = $this->createAuthenticatedClientWithRealPassword();

        $crawler = $client->request('GET', '/admin/settings/');
        $token = (string) $crawler->filter('form[action="/admin/settings/change-email"] input[name="_token"]')->attr('value');

        $client->request('POST', '/admin/settings/change-email', [
            '_token'           => $token,
            'current_password' => self::ADMIN_PASSWORD,
            'new_email'        => 'not-an-email',
        ]);

        self::assertResponseRedirects('/admin/settings/');
        $client->followRedirect();
        self::assertStringContainsString('invalide', (string) $client->getResponse()->getContent());
    }

    public function testChangePasswordSuccess(): void
    {
        $client = $this->createAuthenticatedClientWithRealPassword();

        $crawler = $client->request('GET', '/admin/settings/');
        $token = (string) $crawler->filter('form[action="/admin/settings/change-password"] input[name="_token"]')->attr('value');

        $client->request('POST', '/admin/settings/change-password', [
            '_token'           => $token,
            'current_password' => self::ADMIN_PASSWORD,
            'new_password'     => 'new-secure-password',
            'confirm_password' => 'new-secure-password',
        ]);

        self::assertResponseRedirects('/admin/settings/');
        $client->followRedirect();
        self::assertStringContainsString('succès', (string) $client->getResponse()->getContent());
    }

    public function testChangePasswordRejectsWrongCurrentPassword(): void
    {
        $client = $this->createAuthenticatedClientWithRealPassword();

        $crawler = $client->request('GET', '/admin/settings/');
        $token = (string) $crawler->filter('form[action="/admin/settings/change-password"] input[name="_token"]')->attr('value');

        $client->request('POST', '/admin/settings/change-password', [
            '_token'           => $token,
            'current_password' => 'wrong-password',
            'new_password'     => 'new-secure-password',
            'confirm_password' => 'new-secure-password',
        ]);

        self::assertResponseRedirects('/admin/settings/');
        $client->followRedirect();
        self::assertStringContainsString('incorrect', (string) $client->getResponse()->getContent());
    }

    public function testChangePasswordRejectsTooShortPassword(): void
    {
        $client = $this->createAuthenticatedClientWithRealPassword();

        $crawler = $client->request('GET', '/admin/settings/');
        $token = (string) $crawler->filter('form[action="/admin/settings/change-password"] input[name="_token"]')->attr('value');

        $client->request('POST', '/admin/settings/change-password', [
            '_token'           => $token,
            'current_password' => self::ADMIN_PASSWORD,
            'new_password'     => 'short',
            'confirm_password' => 'short',
        ]);

        self::assertResponseRedirects('/admin/settings/');
        $client->followRedirect();
        self::assertStringContainsString('8 caract', (string) $client->getResponse()->getContent());
    }

    public function testChangePasswordRejectsNonMatchingConfirmation(): void
    {
        $client = $this->createAuthenticatedClientWithRealPassword();

        $crawler = $client->request('GET', '/admin/settings/');
        $token = (string) $crawler->filter('form[action="/admin/settings/change-password"] input[name="_token"]')->attr('value');

        $client->request('POST', '/admin/settings/change-password', [
            '_token'           => $token,
            'current_password' => self::ADMIN_PASSWORD,
            'new_password'     => 'new-secure-password',
            'confirm_password' => 'different-password',
        ]);

        self::assertResponseRedirects('/admin/settings/');
        $client->followRedirect();
        self::assertStringContainsString('correspondent pas', (string) $client->getResponse()->getContent());
    }

    private function createAuthenticatedClientWithRealPassword(): KernelBrowser
    {
        $client = $this->createClientWithFreshDatabase();

        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = (new User())
            ->setEmail('admin-real@test.local')
            ->setRoles(['ROLE_ADMIN']);
        $user->setPassword($passwordHasher->hashPassword($user, self::ADMIN_PASSWORD));

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        $client->loginUser($user);

        return $client;
    }
}
