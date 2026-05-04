<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\ProductImageStorage;
use App\Service\SlugGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/products', name: 'app_admin_product_')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $search = trim((string) $request->query->get('q', ''));
        $visible = self::parseNullableBool($request->query->get('visible'));
        $categoryId = $request->query->getInt('category', 0);
        $categoryId = $categoryId > 0 ? $categoryId : null;

        $pagination = $productRepository->paginateAdminList(
            $search !== '' ? $search : null,
            $visible,
            $categoryId,
            $page
        );

        return $this->render('admin/product/index.html.twig', [
            'products' => $pagination['items'],
            'page' => $page,
            'pages' => $pagination['pages'],
            'total' => $pagination['total'],
            'categories' => $categoryRepository->findBy([], ['name' => 'ASC']),
            'filters' => [
                'q' => $search,
                'visible' => $request->query->get('visible', ''),
                'category' => $request->query->get('category', ''),
            ],
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SlugGenerator $slugGenerator,
        ProductImageStorage $productImageStorage,
    ): Response {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $slugInput = trim($product->getSlug());
            $slugSource = $slugInput !== '' ? $slugInput : $product->getName();
            $product->setSlug($slugGenerator->generateProductSlug($slugSource));

            $coverFile = $form->get('coverFile')->getData();
            $product->setCoverImage($productImageStorage->store($coverFile, $product->getCoverImage()));
            $product->touch();

            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Produit créé avec succès.');

            return $this->redirectToRoute('app_admin_product_index');
        }

        return $this->render('admin/product/new.html.twig', [
            'form' => $form,
            'product' => $product,
            'imagePublicPath' => $productImageStorage->getPublicPath($product->getCoverImage()),
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->redirectToRoute('app_admin_product_edit', ['id' => $product->getId()]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Product $product,
        Request $request,
        EntityManagerInterface $entityManager,
        SlugGenerator $slugGenerator,
        ProductImageStorage $productImageStorage,
    ): Response {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $slugInput = trim($product->getSlug());
            $slugSource = $slugInput !== '' ? $slugInput : $product->getName();
            $product->setSlug($slugGenerator->generateProductSlug($slugSource, $product->getId()));

            $coverFile = $form->get('coverFile')->getData();
            $product->setCoverImage($productImageStorage->store($coverFile, $product->getCoverImage()));
            $product->touch();

            $entityManager->flush();

            $this->addFlash('success', 'Produit mis à jour.');

            return $this->redirectToRoute('app_admin_product_index');
        }

        return $this->render('admin/product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
            'imagePublicPath' => $productImageStorage->getPublicPath($product->getCoverImage()),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(
        Product $product,
        Request $request,
        EntityManagerInterface $entityManager,
        ProductImageStorage $productImageStorage,
    ): Response {
        if ($this->isCsrfTokenValid('delete_product_' . $product->getId(), (string) $request->request->get('_token'))) {
            $productImageStorage->remove($product->getCoverImage());

            $entityManager->remove($product);
            $entityManager->flush();

            $this->addFlash('success', 'Produit supprimé.');
        }

        return $this->redirectToRoute('app_admin_product_index');
    }

    private static function parseNullableBool(mixed $value): ?bool
    {
        if ($value === '1' || $value === 1 || $value === true) {
            return true;
        }

        if ($value === '0' || $value === 0 || $value === false) {
            return false;
        }

        return null;
    }
}
