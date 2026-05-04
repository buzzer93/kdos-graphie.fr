<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\ProductImageStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/catalogue', name: 'app_catalog_')]
class CatalogController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $search = trim((string) $request->query->get('q', ''));
        $category = trim((string) $request->query->get('category', ''));

        $pagination = $productRepository->paginateVisibleForCatalog(
            $search !== '' ? $search : null,
            $category !== '' ? $category : null,
            $page
        );

        return $this->render('catalog/index.html.twig', [
            'products' => $pagination['items'],
            'page' => $page,
            'pages' => $pagination['pages'],
            'total' => $pagination['total'],
            'categories' => $categoryRepository->findVisibleOrderedByName(),
            'filters' => [
                'q' => $search,
                'category' => $category,
            ],
        ]);
    }

    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function show(string $slug, ProductRepository $productRepository, ProductImageStorage $productImageStorage): Response
    {
        $product = $productRepository->findVisibleBySlug($slug);
        if ($product === null) {
            throw $this->createNotFoundException('Produit introuvable.');
        }

        return $this->render('catalog/show.html.twig', [
            'product' => $product,
            'imagePublicPath' => $productImageStorage->getPublicPath($product->getCoverImage()),
        ]);
    }
}
