<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\ProductImageStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/catalogue', name: 'app_catalog_')]
class CatalogController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        return $this->render('catalog/index.html.twig', [
            'categories' => $categoryRepository->findVisibleOrderedByName(),
            'productsByCategory' => $productRepository->findVisibleGroupedByCategory(),
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
