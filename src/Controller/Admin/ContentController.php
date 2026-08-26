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

#[Route('/admin/content', name: 'app_admin_content_')]
final class ContentController extends AbstractController
{
    private const VALID_TABS = ['hero', 'savoirfaire', 'produits', 'contact'];

    /** @var array<string, string> */
    private const HOME_DEFAULTS = [
        // Hero
        'home_hero_badge'           => 'Gravure laser · Atelier artisanal',
        'home_hero_title'           => 'Conçu pour vous,',
        'home_hero_title_highlight' => 'gravé au laser.',
        'home_hero_subtitle'        => 'Pas de série, pas de catalogue standard. Chaque pièce naît d\'une idée — la vôtre — façonnée sur mesure dans notre atelier avec soin et précision laser.',
        'home_hero_stat_1_value'    => '+500',
        'home_hero_stat_1_label'    => 'Créations uniques',
        'home_hero_stat_2_value'    => '100%',
        'home_hero_stat_2_label'    => 'Sur mesure',
        'home_hero_stat_3_value'    => '48h',
        'home_hero_stat_3_label'    => 'Délai express',

        // Savoir-faire
        'home_sf_eyebrow'  => 'Notre savoir-faire',
        'home_sf_title'    => 'Des créations uniques, à votre image',
        'home_sf_subtitle' => 'Chaque projet est conçu sur mesure dans notre atelier, selon vos envies et vos matériaux — pas selon un catalogue.',

        'home_sf_1_icon'  => '🎁',
        'home_sf_1_title' => 'Cadeaux personnalisés',
        'home_sf_1_desc'  => 'Une idée, un message, un souvenir. On grave ce qui compte vraiment pour vous — pour un anniversaire, un mariage, une naissance.',

        'home_sf_2_icon'  => '🏷️',
        'home_sf_2_title' => 'Gravure sur bois',
        'home_sf_2_desc'  => 'Chêne, hêtre, noyer. La chaleur du bois naturel sublimée par une gravure laser, précise et durable.',

        'home_sf_3_icon'  => '🥃',
        'home_sf_3_title' => 'Gravure sur verre',
        'home_sf_3_desc'  => 'Verres, carafes, bouteilles : une finition raffinée gravée au laser, pour vos événements les plus précieux.',

        'home_sf_4_icon'  => '💼',
        'home_sf_4_title' => 'Objets professionnels',
        'home_sf_4_desc'  => 'Trophées, plaques, goodies : votre image de marque gravée sur mesure, dans notre atelier.',

        'home_sf_5_icon'  => '🧵',
        'home_sf_5_title' => 'Cuir & textile',
        'home_sf_5_desc'  => 'Portefeuilles, carnets, ceintures : la personnalisation haut de gamme du cuir véritable, pièce par pièce.',

        'home_sf_6_icon'  => '⚡',
        'home_sf_6_title' => 'Prototypage rapide',
        'home_sf_6_desc'  => 'Du fichier à l\'objet unique en 48h. On travaille à votre rythme, sur votre projet, pas sur une ligne de production.',

        // Produits phares
        'home_produits_eyebrow' => 'Nos créations',
        'home_produits_title'   => 'Produits phares',

        // Contact
        'home_contact_title'    => 'Une idée ? Parlons-en.',
        'home_contact_subtitle' => 'Décrivez-nous ce que vous avez en tête. On vous répond sous 24h avec un devis sur mesure, fait sur la base de votre projet — pas d\'un catalogue.',
    ];

    /** @var array<string, list<string>> */
    private const SECTION_KEYS = [
        'hero' => [
            'home_hero_badge',
            'home_hero_title',
            'home_hero_title_highlight',
            'home_hero_subtitle',
            'home_hero_stat_1_value',
            'home_hero_stat_1_label',
            'home_hero_stat_2_value',
            'home_hero_stat_2_label',
            'home_hero_stat_3_value',
            'home_hero_stat_3_label',
        ],
        'savoirfaire' => [
            'home_sf_eyebrow',
            'home_sf_title',
            'home_sf_subtitle',
            'home_sf_1_icon', 'home_sf_1_title', 'home_sf_1_desc',
            'home_sf_2_icon', 'home_sf_2_title', 'home_sf_2_desc',
            'home_sf_3_icon', 'home_sf_3_title', 'home_sf_3_desc',
            'home_sf_4_icon', 'home_sf_4_title', 'home_sf_4_desc',
            'home_sf_5_icon', 'home_sf_5_title', 'home_sf_5_desc',
            'home_sf_6_icon', 'home_sf_6_title', 'home_sf_6_desc',
        ],
        'produits' => [
            'home_produits_eyebrow',
            'home_produits_title',
        ],
        'contact' => [
            'home_contact_title',
            'home_contact_subtitle',
        ],
    ];

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        SiteSettingRepository $settingRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->ensureHomeDefaults($settingRepository, $entityManager);

        $tab = $request->query->getString('tab', 'hero');
        if (!in_array($tab, self::VALID_TABS, true)) {
            $tab = 'hero';
        }

        $values = [];
        foreach (self::HOME_DEFAULTS as $key => $default) {
            $values[$key] = $settingRepository->getValue($key, $default);
        }

        return $this->render('admin/content/index.html.twig', [
            'tab'    => $tab,
            'values' => $values,
        ]);
    }

    #[Route('/save/{section}', name: 'save', methods: ['POST'])]
    public function save(
        string $section,
        Request $request,
        SiteSettingRepository $settingRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!in_array($section, self::VALID_TABS, true)) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('content_save_' . $section, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action invalide (token CSRF).');

            return $this->redirectToRoute('app_admin_content_index', ['tab' => $section]);
        }

        $keys = self::SECTION_KEYS[$section];
        /** @var array<string, string> $posted */
        $posted = $request->request->all('content');

        foreach ($keys as $key) {
            $setting = $settingRepository->findByKey($key);
            if ($setting === null) {
                continue;
            }

            $newValue = isset($posted[$key]) ? trim($posted[$key]) : '';
            $setting->setSettingValue($newValue);
        }

        $entityManager->flush();
        $this->addFlash('success', 'Contenu enregistré.');

        return $this->redirectToRoute('app_admin_content_index', ['tab' => $section]);
    }

    private function ensureHomeDefaults(SiteSettingRepository $repo, EntityManagerInterface $em): void
    {
        $labels = [
            'home_hero_badge'           => 'Hero — Badge texte',
            'home_hero_title'           => 'Hero — Titre (partie normale)',
            'home_hero_title_highlight' => 'Hero — Titre (partie en amber)',
            'home_hero_subtitle'        => 'Hero — Sous-titre',
            'home_hero_stat_1_value'    => 'Hero — Stat 1 valeur',
            'home_hero_stat_1_label'    => 'Hero — Stat 1 libellé',
            'home_hero_stat_2_value'    => 'Hero — Stat 2 valeur',
            'home_hero_stat_2_label'    => 'Hero — Stat 2 libellé',
            'home_hero_stat_3_value'    => 'Hero — Stat 3 valeur',
            'home_hero_stat_3_label'    => 'Hero — Stat 3 libellé',
            'home_sf_eyebrow'           => 'Savoir-faire — Eyebrow',
            'home_sf_title'             => 'Savoir-faire — Titre',
            'home_sf_subtitle'          => 'Savoir-faire — Sous-titre',
            'home_sf_1_icon'            => 'Savoir-faire — Carte 1 icône',
            'home_sf_1_title'           => 'Savoir-faire — Carte 1 titre',
            'home_sf_1_desc'            => 'Savoir-faire — Carte 1 description',
            'home_sf_2_icon'            => 'Savoir-faire — Carte 2 icône',
            'home_sf_2_title'           => 'Savoir-faire — Carte 2 titre',
            'home_sf_2_desc'            => 'Savoir-faire — Carte 2 description',
            'home_sf_3_icon'            => 'Savoir-faire — Carte 3 icône',
            'home_sf_3_title'           => 'Savoir-faire — Carte 3 titre',
            'home_sf_3_desc'            => 'Savoir-faire — Carte 3 description',
            'home_sf_4_icon'            => 'Savoir-faire — Carte 4 icône',
            'home_sf_4_title'           => 'Savoir-faire — Carte 4 titre',
            'home_sf_4_desc'            => 'Savoir-faire — Carte 4 description',
            'home_sf_5_icon'            => 'Savoir-faire — Carte 5 icône',
            'home_sf_5_title'           => 'Savoir-faire — Carte 5 titre',
            'home_sf_5_desc'            => 'Savoir-faire — Carte 5 description',
            'home_sf_6_icon'            => 'Savoir-faire — Carte 6 icône',
            'home_sf_6_title'           => 'Savoir-faire — Carte 6 titre',
            'home_sf_6_desc'            => 'Savoir-faire — Carte 6 description',
            'home_produits_eyebrow'     => 'Produits phares — Eyebrow',
            'home_produits_title'       => 'Produits phares — Titre',
            'home_contact_title'        => 'Contact CTA — Titre',
            'home_contact_subtitle'     => 'Contact CTA — Sous-titre',
        ];

        $flushed = false;
        foreach (self::HOME_DEFAULTS as $key => $default) {
            if ($repo->findByKey($key) === null) {
                $em->persist(new SiteSetting($key, $labels[$key], $default));
                $flushed = true;
            }
        }

        if ($flushed) {
            $em->flush();
        }
    }
}
