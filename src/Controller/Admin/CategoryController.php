<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Service\SlugGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/categories', name: 'app_admin_category_')]
class CategoryController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, CategoryRepository $categoryRepository): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $search = trim((string) $request->query->get('q', ''));
        $visible = self::parseNullableBool($request->query->get('visible'));

        $pagination = $categoryRepository->paginateAdminList(
            $search !== '' ? $search : null,
            $visible,
            $page
        );

        return $this->render('admin/category/index.html.twig', [
            'categories'    => $pagination['items'],
            'page'          => $page,
            'pages'         => $pagination['pages'],
            'total'         => $pagination['total'],
            'filters'       => [
                'q'       => $search,
                'visible' => $request->query->get('visible', ''),
            ],
            'allCategories' => $categoryRepository->findAllOrderedBySortOrder(),
            'reorderUrl'    => $this->generateUrl('app_admin_category_reorder'),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SlugGenerator $slugGenerator): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $slugInput = trim($category->getSlug());
            $slugSource = $slugInput !== '' ? $slugInput : $category->getName();
            $category->setSlug($slugGenerator->generateCategorySlug($slugSource));

            $entityManager->persist($category);
            $entityManager->flush();

            $this->addFlash('success', 'Catégorie créée avec succès.');

            return $this->redirectToRoute('app_admin_category_index');
        }

        return $this->render('admin/category/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/reorder', name: 'reorder', methods: ['POST'])]
    public function reorder(Request $request, CategoryRepository $categoryRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isCsrfTokenValid('category_reorder', (string) $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['error' => 'Token invalide'], Response::HTTP_FORBIDDEN);
        }

        /** @var array<int, int> $ids */
        $ids = json_decode((string) $request->getContent(), true)['ids'] ?? [];

        if (!is_array($ids)) {
            return new JsonResponse(['error' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        foreach ($ids as $position => $id) {
            $category = $categoryRepository->find((int) $id);
            $category?->setSortOrder($position);
        }

        $entityManager->flush();

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(Category $category): Response
    {
        return $this->redirectToRoute('app_admin_category_edit', ['id' => $category->getId()]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Category $category, Request $request, EntityManagerInterface $entityManager, SlugGenerator $slugGenerator): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $slugInput = trim($category->getSlug());
            $slugSource = $slugInput !== '' ? $slugInput : $category->getName();
            $category->setSlug($slugGenerator->generateCategorySlug($slugSource, $category->getId()));
            $entityManager->flush();

            $this->addFlash('success', 'Catégorie mise à jour.');

            return $this->redirectToRoute('app_admin_category_index');
        }

        return $this->render('admin/category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Category $category, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_category_' . $category->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($category);
            $entityManager->flush();

            $this->addFlash('success', 'Catégorie supprimée.');
        }

        return $this->redirectToRoute('app_admin_category_index');
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
