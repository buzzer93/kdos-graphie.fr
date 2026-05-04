<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OrderItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
#[ORM\Table(name: 'order_item')]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    /** Product name snapshot at order time. */
    #[ORM\Column(length: 255)]
    private string $productName;

    /** Unit price stored in cents. */
    #[ORM\Column]
    private int $unitPrice;

    #[ORM\Column]
    private int $quantity = 1;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $customizationText = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customizationFilePath = null;

    public function getSubtotal(): int
    {
        return $this->unitPrice * $this->quantity;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): static
    {
        $this->productName = $productName;

        return $this;
    }

    public function getUnitPrice(): int
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(int $unitPrice): static
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getCustomizationText(): ?string
    {
        return $this->customizationText;
    }

    public function setCustomizationText(?string $customizationText): static
    {
        $this->customizationText = $customizationText;

        return $this;
    }

    public function getCustomizationFilePath(): ?string
    {
        return $this->customizationFilePath;
    }

    public function setCustomizationFilePath(?string $customizationFilePath): static
    {
        $this->customizationFilePath = $customizationFilePath;

        return $this;
    }
}
