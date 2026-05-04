<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    public const STATUS_A_CONFIRMER = 'a_confirmer';
    public const STATUS_EN_ATTENTE_PAIEMENT = 'en_attente_paiement';
    public const STATUS_A_FAIRE = 'a_faire';
    public const STATUS_TERMINE = 'termine';
    public const STATUS_REFUSE = 'refuse';
    public const STATUS_ANNULE = 'annule';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private string $reference;

    #[ORM\Column(length: 30)]
    private string $status = self::STATUS_A_CONFIRMER;

    #[ORM\Column(length: 255)]
    private string $customerFirstName;

    #[ORM\Column(length: 255)]
    private string $customerLastName;

    #[ORM\Column(length: 255)]
    private string $customerEmail;

    #[ORM\Column(length: 50)]
    private string $customerPhone;

    #[ORM\Column(type: Types::TEXT)]
    private string $shippingAddress;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $additionalInfo = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $decisionReason = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $trackingNumber = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $shippingCarrier = null;

    /** Total stored in cents. */
    #[ORM\Column]
    private int $total;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, OrderItem> */
    #[ORM\OneToMany(
        targetEntity: OrderItem::class,
        mappedBy: 'order',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    private Collection $items;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->items = new ArrayCollection();
        $this->customerFirstName = '';
        $this->customerLastName = '';
        $this->customerPhone = '';
        $this->shippingAddress = '';
    }

    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_A_CONFIRMER => 'A confirmer',
            self::STATUS_EN_ATTENTE_PAIEMENT => 'En attente de paiement',
            self::STATUS_A_FAIRE => 'A faire',
            self::STATUS_TERMINE => 'Termine',
            self::STATUS_REFUSE => 'Refuse',
            self::STATUS_ANNULE => 'Annule',
        ];
    }

    public function getFormattedTotal(): string
    {
        return number_format($this->total / 100, 2, ',', ' ') . ' €';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCustomerFirstName(): string
    {
        return $this->customerFirstName;
    }

    public function setCustomerFirstName(string $customerFirstName): static
    {
        $this->customerFirstName = $customerFirstName;

        return $this;
    }

    public function getCustomerLastName(): string
    {
        return $this->customerLastName;
    }

    public function setCustomerLastName(string $customerLastName): static
    {
        $this->customerLastName = $customerLastName;

        return $this;
    }

    public function getCustomerName(): string
    {
        return trim($this->customerFirstName . ' ' . $this->customerLastName);
    }

    public function getCustomerFullName(): string
    {
        return $this->getCustomerName();
    }

    public function setCustomerName(string $customerName): static
    {
        $parts = preg_split('/\s+/', trim($customerName), 2);
        $firstName = $parts[0] ?? '';
        $lastName = $parts[1] ?? '';

        $this->customerFirstName = $firstName;
        $this->customerLastName = $lastName;

        return $this;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(string $customerEmail): static
    {
        $this->customerEmail = $customerEmail;

        return $this;
    }

    public function getCustomerPhone(): string
    {
        return $this->customerPhone;
    }

    public function setCustomerPhone(string $customerPhone): static
    {
        $this->customerPhone = $customerPhone;

        return $this;
    }

    public function getShippingAddress(): string
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(string $shippingAddress): static
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    public function getAdditionalInfo(): ?string
    {
        return $this->additionalInfo;
    }

    public function setAdditionalInfo(?string $additionalInfo): static
    {
        $this->additionalInfo = $additionalInfo;

        return $this;
    }

    public function getDecisionReason(): ?string
    {
        return $this->decisionReason;
    }

    public function setDecisionReason(?string $decisionReason): static
    {
        $this->decisionReason = $decisionReason;
        
        return $this;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(?string $trackingNumber): static
    {
        $this->trackingNumber = $trackingNumber;

        return $this;
    }

    public function getShippingCarrier(): ?string
    {
        return $this->shippingCarrier;
    }

    public function setShippingCarrier(?string $shippingCarrier): static
    {
        $this->shippingCarrier = $shippingCarrier;

        return $this;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, OrderItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrder($this);
        }

        return $this;
    }

    public function removeItem(OrderItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getOrder() === $this) {
                $item->setOrder(null);
            }
        }

        return $this;
    }
}
