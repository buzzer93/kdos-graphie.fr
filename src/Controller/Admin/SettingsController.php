<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\SiteSetting;
use App\Entity\User;
use App\Repository\SiteSettingRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/admin/settings', name: 'app_admin_settings_')]
final class SettingsController extends AbstractController
{
    /** @var array<string, string> */
    private const CONTACT_DEFAULTS = [
        'contact_phone'     => 'Téléphone',
        'contact_email'     => 'Email de contact public',
        'contact_address'   => 'Adresse de l\'atelier',
        'contact_instagram' => 'Lien Instagram',
        'contact_facebook'  => 'Lien Facebook',
    ];

    #[Route('/', name: 'index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        SiteSettingRepository $settingRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->ensureContactDefaults($settingRepository, $entityManager);

        $settings = $settingRepository->findBy([], ['settingKey' => 'ASC']);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('settings_save', (string) $request->request->get('_token'))) {
                $this->addFlash('danger', 'Action invalide (token CSRF).');

                return $this->redirectToRoute('app_admin_settings_index');
            }

            /** @var array<string, string> $values */
            $values = $request->request->all('settings');

            foreach ($settings as $setting) {
                $newValue = isset($values[$setting->getSettingKey()])
                    ? trim($values[$setting->getSettingKey()])
                    : null;
                $setting->setSettingValue($newValue !== '' ? $newValue : null);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Paramètres enregistrés.');

            return $this->redirectToRoute('app_admin_settings_index');
        }

        $contactKeys = array_keys(self::CONTACT_DEFAULTS);
        $contactSettings = array_filter($settings, static fn (SiteSetting $s) => in_array($s->getSettingKey(), $contactKeys, true));
        $otherSettings = array_filter($settings, static fn (SiteSetting $s) => !in_array($s->getSettingKey(), $contactKeys, true));

        return $this->render('admin/settings/index.html.twig', [
            'contactSettings' => array_values($contactSettings),
            'otherSettings'   => array_values($otherSettings),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('settings_new', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action invalide (token CSRF).');

            return $this->redirectToRoute('app_admin_settings_index');
        }

        $key = trim((string) $request->request->get('setting_key', ''));
        $label = trim((string) $request->request->get('label', ''));

        if ($key === '' || $label === '') {
            $this->addFlash('warning', 'La clé et le libellé sont obligatoires.');

            return $this->redirectToRoute('app_admin_settings_index');
        }

        $setting = new SiteSetting($key, $label);
        $entityManager->persist($setting);
        $entityManager->flush();

        $this->addFlash('success', 'Paramètre "' . $key . '" créé.');

        return $this->redirectToRoute('app_admin_settings_index');
    }

    #[Route('/change-email', name: 'change_email', methods: ['POST'])]
    public function changeEmail(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        TokenStorageInterface $tokenStorage,
    ): Response {
        if (!$this->isCsrfTokenValid('settings_change_email', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action invalide (token CSRF).');

            return $this->redirectToRoute('app_admin_settings_index');
        }

        /** @var User $user */
        $user = $this->getUser();
        $currentPassword = (string) $request->request->get('current_password', '');
        $newEmail = trim((string) $request->request->get('new_email', ''));

        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            $this->addFlash('danger', 'Mot de passe actuel incorrect.');

            return $this->redirectToRoute('app_admin_settings_index');
        }

        if ($newEmail === '' || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('danger', 'Adresse email invalide.');

            return $this->redirectToRoute('app_admin_settings_index');
        }

        if ($userRepository->findOneBy(['email' => $newEmail]) !== null) {
            $this->addFlash('danger', 'Cette adresse email est déjà utilisée.');

            return $this->redirectToRoute('app_admin_settings_index');
        }

        $user->setEmail($newEmail);
        $entityManager->flush();

        $tokenStorage->setToken(null);
        $request->getSession()->invalidate();

        $this->addFlash('success', 'Email modifié. Veuillez vous reconnecter.');

        return $this->redirectToRoute('app_login');
    }

    #[Route('/change-password', name: 'change_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        if (!$this->isCsrfTokenValid('settings_change_password', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action invalide (token CSRF).');

            return $this->redirectToRoute('app_admin_settings_index');
        }

        /** @var User $user */
        $user = $this->getUser();
        $currentPassword = (string) $request->request->get('current_password', '');
        $newPassword = (string) $request->request->get('new_password', '');
        $confirmPassword = (string) $request->request->get('confirm_password', '');

        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            $this->addFlash('danger', 'Mot de passe actuel incorrect.');

            return $this->redirectToRoute('app_admin_settings_index');
        }

        if (strlen($newPassword) < 8) {
            $this->addFlash('danger', 'Le nouveau mot de passe doit contenir au moins 8 caractères.');

            return $this->redirectToRoute('app_admin_settings_index');
        }

        if ($newPassword !== $confirmPassword) {
            $this->addFlash('danger', 'Les mots de passe ne correspondent pas.');

            return $this->redirectToRoute('app_admin_settings_index');
        }

        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        $entityManager->flush();

        $this->addFlash('success', 'Mot de passe modifié avec succès.');

        return $this->redirectToRoute('app_admin_settings_index');
    }

    private function ensureContactDefaults(SiteSettingRepository $repo, EntityManagerInterface $em): void
    {
        foreach (self::CONTACT_DEFAULTS as $key => $label) {
            if ($repo->findByKey($key) === null) {
                $em->persist(new SiteSetting($key, $label));
            }
        }

        $em->flush();
    }
}
