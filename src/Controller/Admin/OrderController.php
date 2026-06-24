<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use App\Service\CustomizationFileStorage;
use App\Service\OrderAdminActionAvailabilityResolver;
use App\Service\OrderArchiveService;
use App\Service\OrderLifecycleService;
use App\Service\OrderMailer;
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
            'statusLabels' => Order::getStatusLabels(),
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(Order $order, OrderAdminActionAvailabilityResolver $resolver, CustomizationFileStorage $fileStorage): Response
    {
        if ($this->isTerminalStatus($order->getStatus())) {
            return $this->render('admin/order/show.html.twig', [
                'order' => $order,
                'statusLabels' => Order::getStatusLabels(),
                'actions' => $resolver->resolve($order),
                'fileStorage' => $fileStorage,
            ]);
        }

        return $this->redirectToRoute('app_admin_order_edit', ['id' => $order->getId()]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Order $order, Request $request, EntityManagerInterface $entityManager, OrderRepository $orderRepository, OrderAdminActionAvailabilityResolver $resolver, CustomizationFileStorage $fileStorage): Response
    {
        if ($this->isTerminalStatus($order->getStatus())) {
            return $this->redirectToRoute('app_admin_order_show', ['id' => $order->getId()]);
        }

        $lockCommercialData = !in_array($order->getStatus(), [Order::STATUS_EN_ATTENTE_DEVIS, Order::STATUS_A_CONFIRMER], true);

        if ($lockCommercialData) {
            $this->addFlash('warning', 'Commande deja engagee: seules les notes internes restent modifiables.');
        }

        $form = $this->createForm(OrderType::class, $order, [
            'lock_commercial_data' => $lockCommercialData,
        ]);
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
            'lockCommercialData' => $lockCommercialData,
            'actions' => $resolver->resolve($order),
            'fileStorage' => $fileStorage,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Order $order, Request $request, EntityManagerInterface $entityManager, OrderArchiveService $orderArchiveService): Response
    {
        if (!$this->isCsrfTokenValid('delete_order_' . $order->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action invalide (token CSRF).');

            return $this->redirectToRoute('app_admin_order_index');
        }

        if (!in_array($order->getStatus(), [Order::STATUS_ANNULE, Order::STATUS_REFUSE, Order::STATUS_TERMINE], true)) {
            $this->addFlash('danger', 'Suppression interdite: seule une commande annulee, refusee ou terminee peut etre supprimee.');

            return $this->redirectToRoute('app_admin_order_edit', ['id' => $order->getId()]);
        }

        $archivedBy = $this->getUser()?->getUserIdentifier();
        $orderArchiveService->archiveAndDelete($order, $archivedBy, 'Suppression admin apres archivage');
        $this->addFlash('success', 'Commande archivee puis supprimee.');

        return $this->redirectToRoute('app_admin_order_index');
    }

    #[Route('/{id}/send-quote', name: 'send_quote', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function sendQuote(Order $order, Request $request, OrderLifecycleService $orderLifecycleService): Response
    {
        if ($order->getStatus() !== Order::STATUS_EN_ATTENTE_DEVIS) {
            $this->addFlash('warning', 'Cette commande n\'est pas en attente de devis.');

            return $this->redirectToRoute('app_admin_order_edit', ['id' => $order->getId()]);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('order_send_quote_' . $order->getId(), (string) $request->request->get('_token'))) {
                $this->addFlash('danger', 'Action invalide (token CSRF).');

                return $this->redirectToRoute('app_admin_order_send_quote', ['id' => $order->getId()]);
            }

            $priceEuros = (float) str_replace(',', '.', (string) $request->request->get('quoted_price', '0'));
            $quotedPriceCents = (int) round($priceEuros * 100);
            $quoteDescription = trim((string) $request->request->get('quote_description', ''));

            if ($quotedPriceCents <= 0) {
                $this->addFlash('danger', 'Le prix devisé doit être supérieur à 0.');

                return $this->redirectToRoute('app_admin_order_send_quote', ['id' => $order->getId()]);
            }

            if ($quoteDescription === '') {
                $this->addFlash('danger', 'La description de la personnalisation est obligatoire.');

                return $this->redirectToRoute('app_admin_order_send_quote', ['id' => $order->getId()]);
            }

            $result = $orderLifecycleService->sendQuote($order, $quotedPriceCents, $quoteDescription);
            $this->addFlash($result->level, $result->message);

            return $this->redirectToRoute('app_admin_order_edit', ['id' => $order->getId()]);
        }

        return $this->render('admin/order/send_quote.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/accept', name: 'accept', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function accept(Order $order, Request $request, OrderLifecycleService $orderLifecycleService): Response
    {
        return $this->handleAction($order, $request, 'accept', static fn () => $orderLifecycleService->accept($order));
    }

    #[Route('/{id}/remind-payment', name: 'remind_payment', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function remindPayment(Order $order, Request $request, OrderLifecycleService $orderLifecycleService): Response
    {
        return $this->handleAction($order, $request, 'remind_payment', static fn () => $orderLifecycleService->remindPayment($order));
    }

    #[Route('/{id}/reject', name: 'reject', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function reject(Order $order, Request $request, OrderLifecycleService $orderLifecycleService): Response
    {
        $reason = (string) $request->request->get('reason', '');

        return $this->handleAction($order, $request, 'reject', static fn () => $orderLifecycleService->reject($order, $reason));
    }

    #[Route('/{id}/cancel', name: 'cancel', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function cancel(Order $order, Request $request, OrderLifecycleService $orderLifecycleService): Response
    {
        $reason = (string) $request->request->get('reason', '');

        return $this->handleAction($order, $request, 'cancel', static fn () => $orderLifecycleService->cancel($order, $reason));
    }

    #[Route('/{id}/mark-paid', name: 'mark_paid', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function markPaid(Order $order, Request $request, OrderLifecycleService $orderLifecycleService): Response
    {
        return $this->handleAction($order, $request, 'mark_paid', static fn () => $orderLifecycleService->markAsPaid($order));
    }

    #[Route('/{id}/complete', name: 'complete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function complete(Order $order, Request $request, OrderLifecycleService $orderLifecycleService): Response
    {
        return $this->handleAction($order, $request, 'complete', static fn () => $orderLifecycleService->complete($order));
    }

    #[Route('/purge', name: 'purge', methods: ['POST'])]
    public function purge(Request $request, OrderRepository $orderRepository, OrderArchiveService $orderArchiveService): Response
    {
        if (!$this->isCsrfTokenValid('order_purge', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action invalide (token CSRF).');

            return $this->redirectToRoute('app_admin_order_index');
        }

        $orders = $orderRepository->findPurgeable(30);

        if (empty($orders)) {
            $this->addFlash('warning', 'Aucune commande éligible à la purge (terminées/refusées/annulées de plus de 30 jours).');

            return $this->redirectToRoute('app_admin_order_index');
        }

        $archivedBy = $this->getUser()?->getUserIdentifier();
        $count = 0;

        foreach ($orders as $order) {
            $orderArchiveService->archiveAndDelete($order, $archivedBy, 'Purge automatique (> 30 jours)');
            ++$count;
        }

        $this->addFlash('success', $count . ' commande(s) archivée(s) et supprimée(s).');

        return $this->redirectToRoute('app_admin_order_index');
    }

    #[Route('/{id}/request-info', name: 'request_info', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function requestInfo(Order $order, Request $request, OrderMailer $orderMailer): Response
    {
        if (!$this->isCsrfTokenValid(
            'order_action_request_info_' . $order->getId(),
            (string) $request->request->get('_token')
        )) {
            $this->addFlash('danger', 'Action invalide (token CSRF).');

            return $this->redirectToRoute('app_admin_order_edit', ['id' => $order->getId()]);
        }

        $message = trim((string) $request->request->get('message', ''));

        if ($message === '') {
            $this->addFlash('warning', 'Le message ne peut pas être vide.');

            return $this->redirectToRoute('app_admin_order_edit', ['id' => $order->getId()]);
        }

        $orderMailer->sendRequestInfo($order, $message);
        $this->addFlash('success', 'Email envoyé au client.');

        return $this->redirectToRoute('app_admin_order_edit', ['id' => $order->getId()]);
    }

    #[Route('/{id}/mark-refunded', name: 'mark_refunded', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function markRefunded(Order $order, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('mark_refunded_' . $order->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action invalide (token CSRF).');

            return $this->redirectToRoute('app_admin_order_show', ['id' => $order->getId()]);
        }

        if ($order->getStatus() !== Order::STATUS_ANNULE) {
            $this->addFlash('warning', 'Le remboursement ne peut etre marque que sur une commande annulee.');

            return $this->redirectToRoute('app_admin_order_show', ['id' => $order->getId()]);
        }

        if ($order->isRefunded()) {
            $this->addFlash('warning', 'Cette commande est deja marquee comme remboursee.');

            return $this->redirectToRoute('app_admin_order_show', ['id' => $order->getId()]);
        }

        $note = trim((string) $request->request->get('refund_note', ''));
        $order->setRefundedAt(new \DateTimeImmutable());
        $order->setRefundNote($note !== '' ? $note : null);
        $entityManager->flush();

        $this->addFlash('success', 'Remboursement enregistre.');

        return $this->redirectToRoute('app_admin_order_show', ['id' => $order->getId()]);
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

    private function handleAction(Order $order, Request $request, string $action, callable $callback): Response
    {
        if (!$this->isCsrfTokenValid(
            'order_action_' . $action . '_' . $order->getId(),
            (string) $request->request->get('_token')
        )) {
            $this->addFlash('danger', 'Action invalide (token CSRF).');

            return $this->redirectToRoute('app_admin_order_edit', ['id' => $order->getId()]);
        }

        $result = $callback();
        $this->addFlash($result->level, $result->message);

        return $this->redirectToRoute('app_admin_order_edit', ['id' => $order->getId()]);
    }

    private function isTerminalStatus(string $status): bool
    {
        return in_array($status, [Order::STATUS_TERMINE, Order::STATUS_REFUSE, Order::STATUS_ANNULE], true);
    }
}
