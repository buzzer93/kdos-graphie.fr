<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\SiteSetting;
use App\Repository\SiteSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/settings', name: 'app_admin_settings_')]
final class SettingsController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        SiteSettingRepository $settingRepository,
        EntityManagerInterface $entityManager,
    ): Response {
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

        return $this->render('admin/settings/index.html.twig', [
            'settings' => $settings,
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
}
