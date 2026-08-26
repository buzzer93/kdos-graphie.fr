<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Product;

final class ProductOptionSerializer
{
    /**
     * Serialize active option groups and their active values for the Stimulus controller.
     *
     * @return array<int, array{id: int, name: string, values: list<array{id: int, label: string, priceAdjustment: int}>}>
     */
    public function serializeForFrontend(Product $product): array
    {
        $result = [];

        foreach ($product->getOptionGroups() as $group) {
            $values = [];
            foreach ($group->getActiveValues() as $value) {
                $values[] = [
                    'id' => (int) $value->getId(),
                    'label' => $value->getLabel(),
                    'priceAdjustment' => $value->getPriceAdjustment(),
                    'isActive' => $value->isActive(),
                ];
            }

            if ($values === []) {
                continue;
            }

            $result[] = [
                'id' => (int) $group->getId(),
                'name' => $group->getName(),
                'values' => $values,
            ];
        }

        return $result;
    }
}
