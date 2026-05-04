<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin', name: 'app_admin_')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'dashboard')]
    public function index(OrderRepository $orderRepository): Response
    {
        $statuses = [
            [
                'code' => Order::STATUS_A_CONFIRMER,
                'label' => 'A confirmer',
            ],
            [
                'code' => Order::STATUS_EN_ATTENTE_PAIEMENT,
                'label' => 'En attente de paiement',
            ],
            [
                'code' => Order::STATUS_A_FAIRE,
                'label' => 'A faire',
            ],
        ];

        $columns = [];
        foreach ($statuses as $status) {
            $columns[] = [
                'code' => $status['code'],
                'label' => $status['label'],
                'count' => $orderRepository->count(['status' => $status['code']]),
            ];
        }

        return $this->render('admin/dashboard.html.twig', [
            'columns' => $columns,
        ]);
    }
}
