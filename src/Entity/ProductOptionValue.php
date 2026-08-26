<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductOptionValueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductOptionValueRepository::class)]
#[ORM\Table(name: 'product_option_value')]
class ProductOptionValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'values')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ProductOptionGroup $group = null;

    /** Ex : "10×10 cm", "Aluminium". */
    #[ORM\Column(length: 100)]
    private string $label;

    /** Price adjustment stored in cents. 0 = no surcharge. */
    #[ORM\Column]
    private int $priceAdjustment = 0;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private int $sortOrder = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGroup(): ?ProductOptionGroup
    {
        return $this->group;
    }

    public function setGroup(?ProductOptionGroup $group): static
    {
        $this->group = $group;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getPriceAdjustment(): int
    {
        return $this->priceAdjustment;
    }

    public function setPriceAdjustment(int $priceAdjustment): static
    {
        $this->priceAdjustment = $priceAdjustment;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }
}
