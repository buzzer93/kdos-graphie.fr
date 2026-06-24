<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\ProductOptionGroup;
use App\Entity\ProductOptionValue;
use App\Form\ProductOptionGroupType;
use App\Form\ProductOptionValueType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/products/{productId}/options', name: 'app_admin_product_option_', requirements: ['productId' => '\\d+'])]
final class ProductOptionController extends AbstractController
{
    #[Route('/new-group', name: 'new_group', methods: ['GET', 'POST'])]
    public function newGroup(int $productId, Request $request, EntityManagerInterface $em): Response
    {
        $product = $em->find(Product::class, $productId);
        if ($product === null) {
            throw $this->createNotFoundException('Produit introuvable.');
        }

        $group = new ProductOptionGroup();
        $group->setProduct($product);

        $form = $this->createForm(ProductOptionGroupType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($group);
            $em->flush();

            $this->addFlash('success', 'Groupe d\'options ajouté.');

            return $this->redirectToRoute('app_admin_product_edit', ['id' => $productId]);
        }

        return $this->render('admin/product/option/new_group.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/group/{groupId}/edit', name: 'edit_group', requirements: ['groupId' => '\\d+'], methods: ['GET', 'POST'])]
    public function editGroup(int $productId, int $groupId, Request $request, EntityManagerInterface $em): Response
    {
        $product = $em->find(Product::class, $productId);
        if ($product === null) {
            throw $this->createNotFoundException('Produit introuvable.');
        }

        $group = $em->find(ProductOptionGroup::class, $groupId);
        if ($group === null || $group->getProduct()?->getId() !== $productId) {
            throw $this->createNotFoundException('Groupe introuvable.');
        }

        $form = $this->createForm(ProductOptionGroupType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Groupe d\'options mis à jour.');

            return $this->redirectToRoute('app_admin_product_edit', ['id' => $productId]);
        }

        return $this->render('admin/product/option/edit_group.html.twig', [
            'product' => $product,
            'group' => $group,
            'form' => $form,
        ]);
    }

    #[Route('/group/{groupId}/delete', name: 'delete_group', requirements: ['groupId' => '\\d+'], methods: ['POST'])]
    public function deleteGroup(int $productId, int $groupId, Request $request, EntityManagerInterface $em): Response
    {
        $group = $em->find(ProductOptionGroup::class, $groupId);

        if (
            $group !== null
            && $group->getProduct()?->getId() === $productId
            && $this->isCsrfTokenValid('delete_option_group_' . $groupId, (string) $request->request->get('_token'))
        ) {
            $em->remove($group);
            $em->flush();

            $this->addFlash('success', 'Groupe d\'options supprimé.');
        }

        return $this->redirectToRoute('app_admin_product_edit', ['id' => $productId]);
    }

    #[Route('/group/{groupId}/values/new', name: 'new_value', requirements: ['groupId' => '\\d+'], methods: ['GET', 'POST'])]
    public function newValue(int $productId, int $groupId, Request $request, EntityManagerInterface $em): Response
    {
        $product = $em->find(Product::class, $productId);
        if ($product === null) {
            throw $this->createNotFoundException('Produit introuvable.');
        }

        $group = $em->find(ProductOptionGroup::class, $groupId);
        if ($group === null || $group->getProduct()?->getId() !== $productId) {
            throw $this->createNotFoundException('Groupe introuvable.');
        }

        $value = new ProductOptionValue();
        $value->setGroup($group);

        $form = $this->createForm(ProductOptionValueType::class, $value);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($value);
            $em->flush();

            $this->addFlash('success', 'Valeur ajoutée.');

            return $this->redirectToRoute('app_admin_product_edit', ['id' => $productId]);
        }

        return $this->render('admin/product/option/new_value.html.twig', [
            'product' => $product,
            'group' => $group,
            'form' => $form,
        ]);
    }

    #[Route('/group/{groupId}/values/{valueId}/edit', name: 'edit_value', requirements: ['groupId' => '\\d+', 'valueId' => '\\d+'], methods: ['GET', 'POST'])]
    public function editValue(int $productId, int $groupId, int $valueId, Request $request, EntityManagerInterface $em): Response
    {
        $product = $em->find(Product::class, $productId);
        if ($product === null) {
            throw $this->createNotFoundException('Produit introuvable.');
        }

        $group = $em->find(ProductOptionGroup::class, $groupId);
        if ($group === null || $group->getProduct()?->getId() !== $productId) {
            throw $this->createNotFoundException('Groupe introuvable.');
        }

        $value = $em->find(ProductOptionValue::class, $valueId);
        if ($value === null || $value->getGroup()?->getId() !== $groupId) {
            throw $this->createNotFoundException('Valeur introuvable.');
        }

        $form = $this->createForm(ProductOptionValueType::class, $value);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Valeur mise à jour.');

            return $this->redirectToRoute('app_admin_product_edit', ['id' => $productId]);
        }

        return $this->render('admin/product/option/edit_value.html.twig', [
            'product' => $product,
            'group' => $group,
            'value' => $value,
            'form' => $form,
        ]);
    }

    #[Route('/group/{groupId}/values/{valueId}/delete', name: 'delete_value', requirements: ['groupId' => '\\d+', 'valueId' => '\\d+'], methods: ['POST'])]
    public function deleteValue(int $productId, int $groupId, int $valueId, Request $request, EntityManagerInterface $em): Response
    {
        $value = $em->find(ProductOptionValue::class, $valueId);

        if (
            $value !== null
            && $value->getGroup()?->getId() === $groupId
            && $value->getGroup()?->getProduct()?->getId() === $productId
            && $this->isCsrfTokenValid('delete_option_value_' . $valueId, (string) $request->request->get('_token'))
        ) {
            $em->remove($value);
            $em->flush();

            $this->addFlash('success', 'Valeur supprimée.');
        }

        return $this->redirectToRoute('app_admin_product_edit', ['id' => $productId]);
    }
}
