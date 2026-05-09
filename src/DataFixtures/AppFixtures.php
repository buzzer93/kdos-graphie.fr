<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\SiteSetting;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $this->createAdminUser($manager);
        $this->createSiteSettings($manager);
        $categories = $this->createCategories($manager);
        $products = $this->createProducts($manager, $categories);
        $this->createOrders($manager, $products);

        $manager->flush();
    }

    private function createAdminUser(ObjectManager $manager): void
    {
        $email = trim((string) ($_ENV['ADMIN_DEFAULT_EMAIL'] ?? $_SERVER['ADMIN_DEFAULT_EMAIL'] ?? 'admin@kdos-graphie.fr'));
        $plainPassword = (string) ($_ENV['ADMIN_DEFAULT_PASSWORD'] ?? $_SERVER['ADMIN_DEFAULT_PASSWORD'] ?? 'Admin1234!');

        $admin = (new User())
            ->setEmail($email)
            ->setRoles(['ROLE_ADMIN']);

        $admin->setPassword($this->passwordHasher->hashPassword($admin, $plainPassword));

        $manager->persist($admin);
    }

    private function createSiteSettings(ObjectManager $manager): void
    {
        $settings = [
            ['contact_phone',     'Téléphone',              '04 76 00 12 34'],
            ['contact_email',     'Email de contact public', 'atelier@kdos-graphie.fr'],
            ['contact_address',   'Adresse de l\'atelier',  "12 rue des Artisans\n38000 Grenoble"],
            ['contact_instagram', 'Lien Instagram',          'https://www.instagram.com/kdosgraphie'],
            ['contact_facebook',  'Lien Facebook',           'https://www.facebook.com/kdosgraphie'],
        ];

        foreach ($settings as [$key, $label, $value]) {
            $manager->persist(new SiteSetting($key, $label, $value));
        }
    }

    /** @return array<string, Category> */
    private function createCategories(ObjectManager $manager): array
    {
        $definitions = [
            [
                'key' => 'bois',
                'name' => 'Bois gravé',
                'slug' => 'bois-grave',
                'description' => 'Objets en bois personnalisés et gravés au laser.',
                'visible' => true,
            ],
            [
                'key' => 'verre',
                'name' => 'Verre et métal',
                'slug' => 'verre-metal',
                'description' => 'Gravures fines sur verre, inox et aluminium.',
                'visible' => true,
            ],
            [
                'key' => 'papeterie',
                'name' => 'Papeterie créative',
                'slug' => 'papeterie-creative',
                'description' => 'Carnets, cartes et supports papier personnalisés.',
                'visible' => true,
            ],
        ];

        $categories = [];

        foreach ($definitions as $definition) {
            $category = (new Category())
                ->setName($definition['name'])
                ->setSlug($definition['slug'])
                ->setDescription($definition['description'])
                ->setIsVisible($definition['visible']);

            $manager->persist($category);
            $categories[$definition['key']] = $category;
        }

        return $categories;
    }

    /**
     * @param array<string, Category> $categories
     *
     * @return list<Product>
     */
    private function createProducts(ObjectManager $manager, array $categories): array
    {
        $definitions = [
            // Bois gravé
            ['name' => 'Planche apéro signature',    'slug' => 'planche-apero-signature',    'price' => 3490, 'category' => 'bois', 'visible' => true,  'description' => 'Planche à découper en chêne massif gravée de votre prénom, d\'une date ou d\'un message. Format généreux, finition huile naturelle. Idéal pour un cadeau de mariage ou d\'anniversaire.'],
            ['name' => 'Porte-clés prénom chêne',    'slug' => 'porte-cles-prenom-chene',    'price' => 1290, 'category' => 'bois', 'visible' => true,  'description' => 'Petit porte-clés en contreplaqué de chêne, gravé au prénom ou à l\'initiale. Forme ovale, anneau en acier inoxydable. Cadeau rapide et personnalisé.'],
            ['name' => 'Cadre citation atelier',     'slug' => 'cadre-citation-atelier',     'price' => 4290, 'category' => 'bois', 'visible' => true,  'description' => 'Cadre mural en bois de bouleau avec citation ou poème gravé en typographie fine. Format A4, livré avec deux attaches murales.'],
            ['name' => 'Boîte à bijoux gravée',      'slug' => 'boite-a-bijoux-gravee',      'price' => 5490, 'category' => 'bois', 'visible' => true,  'description' => 'Boîte à bijoux en bois de tilleul, intérieur velours, couvercle gravé d\'un prénom et d\'un motif floral. Un cadeau précieux pour une occasion unique.'],
            ['name' => 'Règle en bois personnalisée','slug' => 'regle-bois-personnalisee',   'price' => 990,  'category' => 'bois', 'visible' => true,  'description' => 'Règle 30 cm en bouleau naturel gravée du nom de l\'élève ou de l\'entreprise. Parfaite pour un cadeau de rentrée ou un goodies professionnel.'],
            ['name' => 'Plateau apéritif prénom',    'slug' => 'plateau-aperitif-prenom',    'price' => 2890, 'category' => 'bois', 'visible' => true,  'description' => 'Plateau ovale en hêtre massif gravé d\'un prénom et d\'un motif végétal. Bois traité alimentaire, utilisation quotidienne.'],
            // Verre et métal
            ['name' => 'Flûte mariage élégante',     'slug' => 'flute-mariage-elegante',     'price' => 2490, 'category' => 'verre', 'visible' => true, 'description' => 'Paire de flûtes à champagne en cristal avec noms et date du mariage gravés. Présentées dans un écrin en papier kraft recyclé.'],
            ['name' => 'Gourde inox logo pro',       'slug' => 'gourde-inox-logo-pro',       'price' => 2890, 'category' => 'verre', 'visible' => true, 'description' => 'Gourde isotherme 500 ml en inox brossé gravée du logo de votre entreprise ou d\'un message. Garde au chaud 12h, au froid 24h.'],
            ['name' => 'Plaque de porte atelier',    'slug' => 'plaque-de-porte-atelier',    'price' => 1990, 'category' => 'verre', 'visible' => true, 'description' => 'Plaque signalétique en aluminium anodisé, texte et pictogrammes personnalisables. Finition brossée argentée, fixation double-face incluse.'],
            ['name' => 'Verre whisky gravé',         'slug' => 'verre-whisky-grave',         'price' => 1890, 'category' => 'verre', 'visible' => true, 'description' => 'Verre à whisky en verre épais gravé d\'un prénom ou d\'un monogramme. Volume 30 cl, paroi gravée en bas-relief. Idéal pour un cadeau masculin.'],
            ['name' => 'Médaille commémorative',     'slug' => 'medaille-commemorative',     'price' => 2190, 'category' => 'verre', 'visible' => true, 'description' => 'Médaille ronde en laiton brossé gravée d\'une date, d\'un prénom et d\'un motif au choix. Livrée avec cordon et boîte écrin.'],
            // Papeterie créative
            ['name' => 'Carnet cuir initiales',      'slug' => 'carnet-cuir-initiales',      'price' => 3190, 'category' => 'papeterie', 'visible' => true, 'description' => 'Carnet A5 à couverture cuir naturel gravée des initiales ou d\'un monogramme. 192 pages ivoire lignées, reliure cousu à la main.'],
            ['name' => 'Faire-part bohème',          'slug' => 'faire-part-boheme',          'price' => 390,  'category' => 'papeterie', 'visible' => true, 'description' => 'Faire-part de mariage ou de naissance sur papier kraft 350g, gravure au laser pour un rendu texturé unique. Prix à l\'unité, minimum 20 exemplaires.'],
            ['name' => 'Marque-page aquarelle',      'slug' => 'marque-page-aquarelle',      'price' => 590,  'category' => 'papeterie', 'visible' => true, 'description' => 'Marque-page en bois fin avec motif floral ou géométrique gravé au laser. Format 5 × 18 cm, ganse en tissu. Vendu par lot de 3.'],
            ['name' => 'Étiquettes cadeau gravées',  'slug' => 'etiquettes-cadeau-gravees',  'price' => 790,  'category' => 'papeterie', 'visible' => true, 'description' => 'Lot de 10 étiquettes cadeaux en papier coton 300g, gravées d\'un motif et d\'un espace personnalisable. Parfaites pour sublimer vos emballages.'],
            ['name' => 'Affiche typographique',      'slug' => 'affiche-typographique',      'price' => 2490, 'category' => 'papeterie', 'visible' => true, 'description' => 'Affiche 30 × 40 cm sur papier mat 200g, texte et typographie personnalisables. Impression laser pour un rendu précis et contrasté.'],
            // Hors catalogue (non publié)
            ['name' => 'Prototype non publié',       'slug' => 'prototype-non-publie',       'price' => 1590, 'category' => 'bois', 'visible' => false, 'description' => 'Produit test non publié.'],
        ];

        $products = [];

        foreach ($definitions as $definition) {
            $product = (new Product())
                ->setName($definition['name'])
                ->setSlug($definition['slug'])
                ->setDescription($definition['description'])
                ->setPrice($definition['price'])
                ->setIsVisible($definition['visible'])
                ->setCategory($categories[$definition['category']]);

            $manager->persist($product);
            $products[] = $product;
        }

        return $products;
    }

    /**
     * @param list<Product> $products
     */
    private function createOrders(ObjectManager $manager, array $products): void
    {
        $definitions = [
            [
                'reference' => 'ORD-20260504-001',
                'status' => Order::STATUS_A_CONFIRMER,
                'customerFirstName' => 'Lina',
                'customerLastName' => 'Martin',
                'customerEmail' => 'lina.martin@example.test',
                'customerPhone' => '0600000001',
                'shippingAddress' => "12 rue des Ateliers\n75011 Paris",
                'additionalInfo' => 'Interphone MARTIN',
                'notes' => 'Souhaite un emballage cadeau.',
                'items' => [
                    ['productIndex' => 0, 'quantity' => 1],
                    ['productIndex' => 7, 'quantity' => 20],
                ],
            ],
            [
                'reference' => 'ORD-20260504-002',
                'status' => Order::STATUS_EN_ATTENTE_PAIEMENT,
                'customerFirstName' => 'Nora',
                'customerLastName' => 'Petit',
                'customerEmail' => 'nora.petit@example.test',
                'customerPhone' => '0600000002',
                'shippingAddress' => "8 avenue des Roses\n69000 Lyon",
                'additionalInfo' => null,
                'notes' => null,
                'items' => [
                    ['productIndex' => 3, 'quantity' => 4],
                ],
            ],
            [
                'reference' => 'ORD-20260504-003',
                'status' => Order::STATUS_A_FAIRE,
                'customerFirstName' => 'Théo',
                'customerLastName' => 'Lambert',
                'customerEmail' => 'theo.lambert@example.test',
                'customerPhone' => '0600000003',
                'shippingAddress' => "24 place du Marche\n33000 Bordeaux",
                'additionalInfo' => 'Livraison en semaine uniquement.',
                'notes' => 'Livraison en point relais.',
                'items' => [
                    ['productIndex' => 1, 'quantity' => 3],
                    ['productIndex' => 8, 'quantity' => 5],
                ],
            ],
            [
                'reference' => 'ORD-20260504-004',
                'status' => Order::STATUS_TERMINE,
                'customerFirstName' => 'Camille',
                'customerLastName' => 'Robin',
                'customerEmail' => 'camille.robin@example.test',
                'customerPhone' => '0600000004',
                'shippingAddress' => "4 impasse des Vignes\n34000 Montpellier",
                'additionalInfo' => null,
                'notes' => 'Commande urgente déjà reçue.',
                'items' => [
                    ['productIndex' => 6, 'quantity' => 2],
                    ['productIndex' => 4, 'quantity' => 1],
                ],
            ],
            [
                'reference' => 'ORD-20260504-005',
                'status' => Order::STATUS_REFUSE,
                'customerFirstName' => 'Jules',
                'customerLastName' => 'Mercier',
                'customerEmail' => 'jules.mercier@example.test',
                'customerPhone' => '0600000005',
                'shippingAddress' => "1 rue de la Poste\n59000 Lille",
                'additionalInfo' => null,
                'decisionReason' => 'Client indisponible, commande annulée.',
                'notes' => 'Client indisponible, commande annulée.',
                'items' => [
                    ['productIndex' => 2, 'quantity' => 1],
                ],
            ],
        ];

        foreach ($definitions as $definition) {
            $order = (new Order())
                ->setReference($definition['reference'])
                ->setStatus($definition['status'])
                ->setCustomerFirstName($definition['customerFirstName'])
                ->setCustomerLastName($definition['customerLastName'])
                ->setCustomerEmail($definition['customerEmail'])
                ->setCustomerPhone($definition['customerPhone'])
                ->setShippingAddress($definition['shippingAddress'])
                ->setAdditionalInfo($definition['additionalInfo'])
                ->setDecisionReason($definition['decisionReason'] ?? null)
                ->setNotes($definition['notes']);

            $total = 0;

            foreach ($definition['items'] as $itemDefinition) {
                $product = $products[$itemDefinition['productIndex']];
                $item = (new OrderItem())
                    ->setProductName($product->getName())
                    ->setUnitPrice($product->getPrice())
                    ->setQuantity($itemDefinition['quantity']);

                $order->addItem($item);
                $total += $item->getSubtotal();
            }

            $order->setTotal($total);
            $manager->persist($order);
        }
    }
}
