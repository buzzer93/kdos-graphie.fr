<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductOptionGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductOptionGroupRepository::class)]
#[ORM\Table(name: 'product_option_group')]
class ProductOptionGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'optionGroups')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Product $product = null;

    /** Ex : "Taille", "Support". */
    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column]
    private int $sortOrder = 0;

    /** @var Collection<int, ProductOptionValue> */
    #[ORM\OneToMany(targetEntity: ProductOptionValue::class, mappedBy: 'group', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC', 'id' => 'ASC'])]
    private Collection $values;

    public function __construct()
    {
        $this->values = new ArrayCollection();
    }

    public function getActiveValues(): Collection
    {
        return $this->values->filter(static fn (ProductOptionValue $v) => $v->isActive());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    /** @return Collection<int, ProductOptionValue> */
    public function getValues(): Collection
    {
        return $this->values;
    }

    public function addValue(ProductOptionValue $value): static
    {
        if (!$this->values->contains($value)) {
            $this->values->add($value);
            $value->setGroup($this);
        }

        return $this;
    }

    public function removeValue(ProductOptionValue $value): static
    {
        $this->values->removeElement($value);

        return $this;
    }
}
