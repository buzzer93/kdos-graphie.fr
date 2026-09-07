<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\ProductImage;
use App\Form\ProductImageType;
use App\Service\ProductImageStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/products/{productId}/images', name: 'app_admin_product_gallery_', requirements: ['productId' => '\\d+'])]
final class ProductGalleryController extends AbstractController
{
    #[Route('/new', name: 'new', methods: ['POST'])]
    public function new(
        int $productId,
        Request $request,
        EntityManagerInterface $em,
        ProductImageStorage $productImageStorage,
    ): Response {
        $product = $em->find(Product::class, $productId);
        if ($product === null) {
            throw $this->createNotFoundException('Produit introuvable.');
        }

        $image = new ProductImage();
        $form = $this->createForm(ProductImageType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $filename = $productImageStorage->store($form->get('imageFile')->getData());
            $image->setFilename((string) $filename);
            $image->setSortOrder($this->nextSortOrder($product));
            $product->addImage($image);

            $em->persist($image);
            $em->flush();

            $this->addFlash('success', 'Image ajoutée au slider.');
        } else {
            $this->addFlash('danger', 'Impossible d\'ajouter cette image (format ou taille invalide).');
        }

        return $this->redirectToRoute('app_admin_product_edit', ['id' => $productId]);
    }

    #[Route('/reorder', name: 'reorder', methods: ['POST'])]
    public function reorder(int $productId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('product_gallery_reorder_' . $productId, (string) $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['error' => 'Token invalide'], Response::HTTP_FORBIDDEN);
        }

        /** @var mixed $ids */
        $ids = json_decode((string) $request->getContent(), true)['ids'] ?? [];

        if (!is_array($ids)) {
            return new JsonResponse(['error' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        foreach ($ids as $position => $id) {
            $image = $em->find(ProductImage::class, (int) $id);
            if ($image !== null && $image->getProduct()?->getId() === $productId) {
                $image->setSortOrder((int) $position);
            }
        }

        $em->flush();

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/{imageId}/delete', name: 'delete', requirements: ['imageId' => '\\d+'], methods: ['POST'])]
    public function delete(
        int $productId,
        int $imageId,
        Request $request,
        EntityManagerInterface $em,
        ProductImageStorage $productImageStorage,
    ): Response {
        $image = $em->find(ProductImage::class, $imageId);

        if (
            $image !== null
            && $image->getProduct()?->getId() === $productId
            && $this->isCsrfTokenValid('delete_product_image_' . $imageId, (string) $request->request->get('_token'))
        ) {
            $productImageStorage->remove($image->getFilename());

            $em->remove($image);
            $em->flush();

            $this->addFlash('success', 'Image supprimée du slider.');
        }

        return $this->redirectToRoute('app_admin_product_edit', ['id' => $productId]);
    }

    private function nextSortOrder(Product $product): int
    {
        $max = -1;
        foreach ($product->getImages() as $image) {
            $max = max($max, $image->getSortOrder());
        }

        return $max + 1;
    }
}
