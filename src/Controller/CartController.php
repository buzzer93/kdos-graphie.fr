<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ProductOptionValueRepository;
use App\Repository\ProductRepository;
use App\Service\CartService;
use App\Service\CustomizationFileStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/panier', name: 'app_cart_')]
final class CartController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(CartService $cartService, CustomizationFileStorage $customizationFileStorage): Response
    {
        return $this->render('cart/index.html.twig', [
            'lines' => $cartService->getLines(),
            'itemsCount' => $cartService->getItemsCount(),
            'totalCents' => $cartService->getTotalCents(),
            'fileStorage' => $customizationFileStorage,
        ]);
    }

    #[Route('/ajouter/{id}', name: 'add', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function add(
        int $id,
        Request $request,
        ProductRepository $productRepository,
        ProductOptionValueRepository $optionValueRepository,
        CartService $cartService,
        CustomizationFileStorage $customizationFileStorage,
        ValidatorInterface $validator,
    ): Response {
        if (!$this->isCsrfTokenValid('cart_add_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action invalide (token CSRF).');

            return $this->redirectToRoute('app_catalog_show', ['slug' => (string) $request->request->get('product_slug', '')]);
        }

        $product = $productRepository->find($id);
        if ($product === null || !$product->isVisible()) {
            throw $this->createNotFoundException('Produit introuvable.');
        }

        $quantity = max(1, $request->request->getInt('quantity', 1));
        $customizationText = trim((string) $request->request->get('customization_text', ''));
        $customizationText = $customizationText !== '' ? $customizationText : null;
        $specialRequest = trim((string) $request->request->get('special_request', ''));
        $specialRequest = $specialRequest !== '' ? $specialRequest : null;

        $uploadedFile = $request->files->get('customization_file');
        $storedFilename = null;

        if ($uploadedFile !== null) {
            $violations = $validator->validate($uploadedFile, [
                new File(
                    maxSize: CustomizationFileStorage::MAX_FILE_SIZE_BYTES,
                    mimeTypes: ['image/png', 'image/jpeg', 'image/webp', 'application/pdf'],
                    mimeTypesMessage: 'Formats autorises: png, jpg/jpeg, webp, pdf.',
                    maxSizeMessage: 'Le fichier de personnalisation ne doit pas depasser 15 Mo.',
                ),
            ]);

            if (count($violations) > 0) {
                $this->addFlash('danger', (string) $violations[0]->getMessage());

                return $this->redirectToRoute('app_catalog_show', ['slug' => $product->getSlug()]);
            }

            $storedFilename = $customizationFileStorage->store($uploadedFile);
        }

        // Resolve option values server-side; price is never trusted from the client.
        $submittedIds = array_filter(
            array_map('intval', (array) $request->request->all('option_values')),
            static fn (int $v) => $v > 0,
        );

        $selectedValues = [];
        $optionValueIds = [];
        $priceAdjustment = 0;

        if ($submittedIds !== []) {
            $values = $optionValueRepository->findByIds($submittedIds);

            foreach ($values as $value) {
                // Security: ensure each value belongs to a group of this product.
                if ($value->getGroup()?->getProduct()?->getId() !== $product->getId()) {
                    continue;
                }
                if (!$value->isActive()) {
                    continue;
                }
                $selectedValues[] = $value->getLabel();
                $optionValueIds[] = (int) $value->getId();
                $priceAdjustment += $value->getPriceAdjustment();
            }
        }

        $unitPrice = $product->getPrice() + $priceAdjustment;
        $optionsSummary = $selectedValues !== [] ? implode(' — ', $selectedValues) : null;

        $cartService->addLine(
            $product,
            $quantity,
            $customizationText,
            $storedFilename,
            $unitPrice,
            $optionValueIds,
            $optionsSummary,
            $specialRequest,
        );

        $this->addFlash('cart_added', $product->getName());

        return $this->redirectToRoute('app_catalog_show', ['slug' => $product->getSlug()]);
    }

    #[Route('/ligne/{lineId}/quantite', name: 'update_quantity', methods: ['POST'])]
    public function updateQuantity(string $lineId, Request $request, CartService $cartService): Response
    {
        if (!$this->isCsrfTokenValid('cart_update_' . $lineId, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action invalide (token CSRF).');

            return $this->redirectToRoute('app_cart_index');
        }

        $quantity = max(1, $request->request->getInt('quantity', 1));
        $cartService->updateQuantity($lineId, $quantity);

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/ligne/{lineId}/supprimer', name: 'remove', methods: ['POST'])]
    public function remove(
        string $lineId,
        Request $request,
        CartService $cartService,
        CustomizationFileStorage $customizationFileStorage,
    ): Response {
        if (!$this->isCsrfTokenValid('cart_remove_' . $lineId, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action invalide (token CSRF).');

            return $this->redirectToRoute('app_cart_index');
        }

        $removedLine = $cartService->removeLine($lineId);
        if ($removedLine !== null) {
            $customizationFileStorage->remove($removedLine['customizationFilePath'] ?? null);
        }

        return $this->redirectToRoute('app_cart_index');
    }
}
