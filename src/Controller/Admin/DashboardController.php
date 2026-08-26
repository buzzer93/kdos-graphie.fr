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
        $activeStatuses = [
            [
                'code'  => Order::STATUS_EN_ATTENTE_DEVIS,
                'label' => 'Devis à envoyer',
                'color' => 'orange',
            ],
            [
                'code'  => Order::STATUS_A_CONFIRMER,
                'label' => 'À confirmer',
                'color' => 'neutral',
            ],
            [
                'code'  => Order::STATUS_EN_ATTENTE_PAIEMENT,
                'label' => 'En attente de paiement',
                'color' => 'blue',
            ],
            [
                'code'  => Order::STATUS_A_FAIRE,
                'label' => 'À faire',
                'color' => 'amber',
            ],
        ];

        $archivedStatuses = [
            [
                'code'  => Order::STATUS_TERMINE,
                'label' => 'Terminées',
                'color' => 'green',
            ],
            [
                'code'  => Order::STATUS_REFUSE,
                'label' => 'Refusées',
                'color' => 'red',
            ],
            [
                'code'  => Order::STATUS_ANNULE,
                'label' => 'Annulées',
                'color' => 'rose',
            ],
        ];

        $buildCards = static function (array $statuses) use ($orderRepository): array {
            return array_map(static function (array $s) use ($orderRepository): array {
                return [
                    'code'  => $s['code'],
                    'label' => $s['label'],
                    'color' => $s['color'],
                    'count' => $orderRepository->count(['status' => $s['code']]),
                ];
            }, $statuses);
        };

        $revenueCents = $orderRepository->getRevenueCentsExcludingRejected();

        return $this->render('admin/dashboard.html.twig', [
            'activeCards'   => $buildCards($activeStatuses),
            'archivedCards' => $buildCards($archivedStatuses),
            'revenueCents'  => $revenueCents,
        ]);
    }
}
