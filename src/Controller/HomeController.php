<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\SiteSettingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        ProductRepository $productRepository,
        SiteSettingRepository $settingRepository,
    ): Response {
        $products = $productRepository->findBy(
            ['isVisible' => true],
            ['createdAt' => 'DESC'],
            8
        );

        return $this->render('home/index.html.twig', [
            'products' => $products,
            'home'     => $this->buildHomeContent($settingRepository),
        ]);
    }

    /** @return array<string, string> */
    private function buildHomeContent(SiteSettingRepository $repo): array
    {
        $defaults = [
            'hero_badge'           => 'Gravure laser · Atelier artisanal',
            'hero_title'           => 'Conçu pour vous,',
            'hero_title_highlight' => 'gravé au laser.',
            'hero_subtitle'        => 'Pas de série, pas de catalogue standard. Chaque pièce naît d\'une idée — la vôtre — façonnée sur mesure dans notre atelier avec soin et précision laser.',
            'hero_stat_1_value'    => '+500',
            'hero_stat_1_label'    => 'Créations uniques',
            'hero_stat_2_value'    => '100%',
            'hero_stat_2_label'    => 'Sur mesure',
            'hero_stat_3_value'    => '48h',
            'hero_stat_3_label'    => 'Délai express',
            'sf_eyebrow'           => 'Notre savoir-faire',
            'sf_title'             => 'Des créations uniques, à votre image',
            'sf_subtitle'          => 'Chaque projet est conçu sur mesure dans notre atelier, selon vos envies et vos matériaux — pas selon un catalogue.',
            'sf_1_icon'            => '🎁',
            'sf_1_title'           => 'Cadeaux personnalisés',
            'sf_1_desc'            => 'Une idée, un message, un souvenir. On grave ce qui compte vraiment pour vous — pour un anniversaire, un mariage, une naissance.',
            'sf_2_icon'            => '🏷️',
            'sf_2_title'           => 'Gravure sur bois',
            'sf_2_desc'            => 'Chêne, hêtre, noyer. La chaleur du bois naturel sublimée par une gravure laser, précise et durable.',
            'sf_3_icon'            => '🥃',
            'sf_3_title'           => 'Gravure sur verre',
            'sf_3_desc'            => 'Verres, carafes, bouteilles : une finition raffinée gravée au laser, pour vos événements les plus précieux.',
            'sf_4_icon'            => '💼',
            'sf_4_title'           => 'Objets professionnels',
            'sf_4_desc'            => 'Trophées, plaques, goodies : votre image de marque gravée sur mesure, dans notre atelier.',
            'sf_5_icon'            => '🧵',
            'sf_5_title'           => 'Cuir & textile',
            'sf_5_desc'            => 'Portefeuilles, carnets, ceintures : la personnalisation haut de gamme du cuir véritable, pièce par pièce.',
            'sf_6_icon'            => '⚡',
            'sf_6_title'           => 'Prototypage rapide',
            'sf_6_desc'            => 'Du fichier à l\'objet unique en 48h. On travaille à votre rythme, sur votre projet, pas sur une ligne de production.',
            'produits_eyebrow'     => 'Nos créations',
            'produits_title'       => 'Produits phares',
            'contact_title'        => 'Une idée ? Parlons-en.',
            'contact_subtitle'     => 'Décrivez-nous ce que vous avez en tête. On vous répond sous 24h avec un devis sur mesure, fait sur la base de votre projet — pas d\'un catalogue.',
        ];

        $content = [];
        foreach ($defaults as $shortKey => $default) {
            $content[$shortKey] = $repo->getValue('home_' . $shortKey, $default);
        }

        return $content;
    }
}
