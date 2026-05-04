<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
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
            ['name' => 'Planche apéro signature', 'slug' => 'planche-apero-signature', 'price' => 3490, 'stock' => 12, 'category' => 'bois', 'visible' => true],
            ['name' => 'Porte-clés prénom chêne', 'slug' => 'porte-cles-prenom-chene', 'price' => 1290, 'stock' => 38, 'category' => 'bois', 'visible' => true],
            ['name' => 'Cadre citation atelier', 'slug' => 'cadre-citation-atelier', 'price' => 4290, 'stock' => 7, 'category' => 'bois', 'visible' => true],
            ['name' => 'Flûte mariage élégante', 'slug' => 'flute-mariage-elegante', 'price' => 2490, 'stock' => 16, 'category' => 'verre', 'visible' => true],
            ['name' => 'Gourde inox logo pro', 'slug' => 'gourde-inox-logo-pro', 'price' => 2890, 'stock' => 22, 'category' => 'verre', 'visible' => true],
            ['name' => 'Plaque de porte atelier', 'slug' => 'plaque-de-porte-atelier', 'price' => 1990, 'stock' => 15, 'category' => 'verre', 'visible' => true],
            ['name' => 'Carnet cuir initiales', 'slug' => 'carnet-cuir-initiales', 'price' => 3190, 'stock' => 10, 'category' => 'papeterie', 'visible' => true],
            ['name' => 'Faire-part bohème', 'slug' => 'faire-part-boheme', 'price' => 390, 'stock' => 220, 'category' => 'papeterie', 'visible' => true],
            ['name' => 'Marque-page aquarelle', 'slug' => 'marque-page-aquarelle', 'price' => 590, 'stock' => 140, 'category' => 'papeterie', 'visible' => true],
            ['name' => 'Prototype non publié', 'slug' => 'prototype-non-publie', 'price' => 1590, 'stock' => 2, 'category' => 'bois', 'visible' => false],
        ];

        $products = [];

        foreach ($definitions as $definition) {
            $product = (new Product())
                ->setName($definition['name'])
                ->setSlug($definition['slug'])
                ->setDescription('Produit de démonstration pour le catalogue et les tests locaux.')
                ->setPrice($definition['price'])
                ->setStock($definition['stock'])
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
