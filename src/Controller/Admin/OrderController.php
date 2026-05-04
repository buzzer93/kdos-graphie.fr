<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/orders', name: 'app_admin_order_')]
class OrderController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, OrderRepository $orderRepository): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $search = trim((string) $request->query->get('q', ''));
        $status = trim((string) $request->query->get('status', ''));

        $pagination = $orderRepository->paginateAdminList(
            $search !== '' ? $search : null,
            $status !== '' ? $status : null,
            $page
        );

        return $this->render('admin/order/index.html.twig', [
            'orders' => $pagination['items'],
            'page' => $page,
            'pages' => $pagination['pages'],
            'total' => $pagination['total'],
            'statusLabels' => Order::getStatusLabels(),
            'filters' => [
                'q' => $search,
                'status' => $status,
            ],
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, OrderRepository $orderRepository): Response
    {
        $order = new Order();
        $order->setReference($this->generateReference($orderRepository));

        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($orderRepository->existsReference($order->getReference())) {
                $form->get('reference')->addError(new FormError('Cette référence existe déjà.'));
            } else {
                $this->recalculateTotal($order);
                $entityManager->persist($order);
                $entityManager->flush();

                $this->addFlash('success', 'Commande créée avec succès.');

                return $this->redirectToRoute('app_admin_order_index');
            }
        }

        return $this->render('admin/order/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('admin/order/show.html.twig', [
            'order' => $order,
            'statusLabels' => Order::getStatusLabels(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Order $order, Request $request, EntityManagerInterface $entityManager, OrderRepository $orderRepository): Response
    {
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($orderRepository->existsReference($order->getReference(), $order->getId())) {
                $form->get('reference')->addError(new FormError('Cette référence existe déjà.'));
            } else {
                $this->recalculateTotal($order);
                $entityManager->flush();

                $this->addFlash('success', 'Commande mise à jour.');

                return $this->redirectToRoute('app_admin_order_index');
            }
        }

        return $this->render('admin/order/edit.html.twig', [
            'order' => $order,
            'form' => $form,
            'statusLabels' => Order::getStatusLabels(),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Order $order, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_order_' . $order->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($order);
            $entityManager->flush();

            $this->addFlash('success', 'Commande supprimée.');
        }

        return $this->redirectToRoute('app_admin_order_index');
    }

    private function recalculateTotal(Order $order): void
    {
        $total = 0;
        foreach ($order->getItems() as $item) {
            $total += $item->getSubtotal();
        }

        $order->setTotal($total);
    }

    private function generateReference(OrderRepository $orderRepository): string
    {
        do {
            $reference = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while ($orderRepository->existsReference($reference));

        return $reference;
    }
}
